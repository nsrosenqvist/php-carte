<?php

declare(strict_types=1);

namespace Carte;

use Alexanderpas\Common\HTTP\ReasonPhrase;
use Alexanderpas\Common\HTTP\StatusCode;
use Carte\Content\ContentResolverInterface;
use Carte\Exceptions\InvalidRedirectException;
use Carte\Exceptions\RouteNotFoundException;
use Carte\Exceptions\UnsupportedMethodException;
use Carte\Http\Method;
use Carte\Routes\RouteCase;
use Carte\Routes\RouteCaseShort;
use Carte\Routes\RouteMatch;
use Carte\Strategies\Strategy;
use Carte\Strategies\StrategyInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * @phpstan-import-type RouteCaseDefinition from RouteCase
 * @phpstan-import-type RouteCaseShortDefinition from RouteCaseShort
 */
class Router implements RequestHandlerInterface
{
    /**
     * Manifest data store
     */
    protected Manifest $manifest;

    /**
     * Response factory
     */
    protected ResponseFactoryInterface $responseFactory;

    /**
     * Stream factory
     */
    protected StreamFactoryInterface $streamFactory;

    /**
     * Default strategy
     */
    protected StrategyInterface $defaultStrategy;

    /**
     * Registered content resolvers
     *
     * @var ContentResolverInterface[]
     */
    protected array $resolvers = [];

    /**
     * Default response
     */
    protected ?ResponseInterface $defaultResponse;

    /**
     * Should throw exceptions
     */
    protected bool $failGracefully;

    /**
     * Root URI
     */
    protected string $rootUri;

    /**
     * @param ResponseFactoryInterface   $responseFactory PSR-17 response factory
     * @param StreamFactoryInterface     $streamFactory   PSR-17 stream factory
     * @param Manifest                   $manifest        Route manifest
     * @param ContentResolverInterface[] $resolvers       Content resolvers
     * @param StrategyInterface|null     $defaultStrategy Default middleware stack
     * @param ResponseInterface|null     $defaultResponse Default response
     * @param bool                       $failGracefully  Fail gracefully or throw exceptions
     * @param string                     $rootUri         Root URI
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        Manifest $manifest,
        array $resolvers = [],
        ?StrategyInterface $defaultStrategy = null,
        ?ResponseInterface $defaultResponse = null,
        bool $failGracefully = true,
        string $rootUri = '',
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->setManifest($manifest);
        $this->setDefaultStrategy($defaultStrategy ?? new Strategy());
        $this->registerContentResolvers(...$resolvers);
        $this->setDefaultResponse($defaultResponse);
        $this->setFailGracefully($failGracefully);
        $this->setRootUri($rootUri);
    }

    public function setRootUri(string $uri): static
    {
        $this->rootUri = trim($uri, '/');

        return $this;
    }

    public function setManifest(Manifest $manifest): static
    {
        $this->manifest = $manifest;

        return $this;
    }

    public function setDefaultResponse(?ResponseInterface $response): static
    {
        $this->defaultResponse = $response;

        return $this;
    }

    public function setDefaultStrategy(StrategyInterface $defaultStrategy): static
    {
        $this->defaultStrategy = $defaultStrategy;

        return $this;
    }

    public function setFailGracefully(bool $failGracefully): static
    {
        $this->failGracefully = $failGracefully;

        return $this;
    }

    public function registerContentResolvers(ContentResolverInterface ...$resolvers): static
    {
        $this->resolvers = array_merge(
            $this->resolvers,
            array_combine(
                array_map(static fn (ContentResolverInterface $resolver) => $resolver::class, $resolvers),
                $resolvers,
            ),
        );

        return $this;
    }

    /**
     * Router that returns a response if a match is found for the request. If no default
     * response is set, a RouteNotFoundException is thrown.
     *
     * @throws RouteNotFoundException
     * @throws UnsupportedMethodException
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $uri = $request->getUri();
        $params = $request->getQueryParams();
        $method = Method::tryFromName($request->getMethod());

        if (! $method) {
            throw new UnsupportedMethodException("Invalid method: {$request->getMethod()}");
        }

        // Remove root URI from endpoint
        $path = $uri->getPath();
        $endpoint = str_starts_with($path, $this->rootUri)
            ? substr($path, strlen($this->rootUri))
            : $path;

        // Endpoints are normalized with "index" suffix
        $endpoint = str_ends_with($endpoint, '/')
            ? ltrim("{$endpoint}index", '/')
            : ltrim($endpoint, '/');

        $result = null;
        $pattern = '';

        // Loop through all routes in descending order of specificity
        foreach ($this->manifest as $pattern => $definitions) {
            // Skip if the pattern does not generally match the endpoint
            if (! $this->isPossiblePattern($pattern, $endpoint)) {
                continue;
            }

            // Loop through all route cases in descending order of specificity
            foreach ($definitions as $route) {
                $match = RouteMatch::fromArray($pattern, $route['match'] ?? []);

                if (! $match->evaluate($endpoint, $method, $params)) {
                    continue;
                }

                $result = $route;
                break 2;
            }
        }

        // Embed the matched route in the request
        $route = ($result !== null) ? $this->createRoute($pattern, $result) : null;
        $request = $request->withAttribute('route', $route);

        // Execute strategy
        $strategy = $result['strategy'] ?? $this->defaultStrategy;
        $strategy = is_string($strategy) ? new $strategy() : $strategy;
        $pipeline = new MiddlewarePipeline($strategy, $this);

        // The middleware pipeline will process all middlewares, which
        // can either return a response or defer to the router. When
        // deferred, the router's handle method will be called, which
        // will then return the response from the matched route.
        return $pipeline->handle($request);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Verify that the request has been routed
        if (! in_array('route', array_keys($request->getAttributes()))) {
            return $this->dispatch($request);
        }

        /** @var RouteCase|null $route */
        $route = $request->getAttribute('route');

        // If no matching route was found, use the default response if it exists
        if (! $route) {
            if ($this->defaultResponse) {
                return $this->defaultResponse;
            }

            return $this->panic(new RouteNotFoundException('Route not found: ' . $request->getUri()));
        }

        // Process the route and its response, returning the resolved content
        return $this->resolveRoute($route, $request);
    }

    /**
     * @param string                                       $pattern    Route pattern
     * @param RouteCaseDefinition|RouteCaseShortDefinition $definition Route definition
     */
    protected function createRoute(string $pattern, mixed $definition): RouteCase
    {
        return (! is_array($definition))
            ? RouteCaseShort::fromArray($pattern, [$definition])
            : RouteCase::fromArray($pattern, $definition);
    }

    /**
     * @throws InvalidRedirectException
     */
    protected function resolveRoute(RouteCase $route, ServerRequestInterface $request): ResponseInterface
    {
        $method = Method::tryFromName($route->match->method?->value) ?? Method::ANY;
        $code = $route->code ?? $this->getDefaultStatusCode($method);
        $headers = $route->headers;

        try {
            $body = $this->resolveContent($route, $request, $code, $headers);
        } catch (Throwable $e) {
            return $this->panic($e);
        }

        $reason = $route->reason ?? ReasonPhrase::tryFromInteger($code)?->value ?? '';
        $version = (string) ($route->version ?? '1.1');

        return $this->createResponse($code, $reason, $version, $headers, $body);
    }

    /**
     * Abort the request with an exception or a response
     *
     * @throws Throwable
     */
    protected function panic(Throwable $e): ResponseInterface
    {
        if (! $this->failGracefully) {
            throw $e;
        }

        $code = StatusCode::tryFromInteger($e->getCode())?->value ?? 500;
        $reason = ReasonPhrase::tryFromInteger($code)?->value ?? ReasonPhrase::HTTP_500->value;

        return $this->createResponse($code, $reason, '1.1', [], $reason);
    }

    /**
     * @param int                         $code    HTTP status code
     * @param string                      $reason  Reason phrase
     * @param string                      $version Protocol version
     * @param array<string, string>       $headers HTTP headers
     * @param StreamInterface|string|null $body    Response body
     */
    protected function createResponse(
        int $code,
        string $reason,
        string $version,
        array $headers,
        StreamInterface|string|null $body,
    ): ResponseInterface {
        $response = $this->responseFactory->createResponse($code, $reason)
            ->withProtocolVersion($version);

        if (is_string($body) && ! empty($body)) {
            $body = $this->streamFactory->createStream($body);
        }

        if ($body) {
            $response = $response->withBody($body);
        }

        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        return $response;
    }

    /**
     * Get default status code for method
     */
    protected function getDefaultStatusCode(Method $method): int
    {
        return match ($method) {
            Method::POST => 201,
            Method::PUT, Method::PATCH, Method::DELETE => 204,
            default => 200,
        };
    }

    /**
     * @param RouteCase              $route   Matching route
     * @param ServerRequestInterface $request Request object
     * @param int                    $code    HTTP status code
     * @param array<string, string>  $headers HTTP headers
     */
    protected function resolveContent(RouteCase $route, ServerRequestInterface $request, int &$code, array &$headers): StreamInterface|string
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->supports($route)) {
                return $resolver->resolve($route, $request, $code, $headers);
            }
        }

        return $route->body ?: '';
    }

    protected function isPossiblePattern(string $pattern, string $endpoint): bool
    {
        return (strpos($pattern, '{') === false)
            ? fnmatch($pattern, $endpoint)
            : fnmatch(preg_replace('/{(.+?)}/', '*', $pattern) ?: '', $endpoint);
    }
}
