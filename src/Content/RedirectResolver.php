<?php

declare(strict_types=1);

namespace Carte\Content;

use Alexanderpas\Common\HTTP\ReasonPhrase;
use Carte\Content\ContentResolverInterface;
use Carte\Routes\RouteCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

use function Carte\is_http_redirect;
use function Carte\str_first_line;

class RedirectResolver implements ContentResolverInterface
{
    public function __construct(
        protected ?string $root = null,
    ) {} // phpcs:ignore

    /**
     * @param RouteCase              $route       Matching route
     * @param ServerRequestInterface $request     Request object
     * @param int                    $httpCode    HTTP status code
     * @param array<string, string>  $httpHeaders HTTP headers
     *
     * @param-out int                   $httpCode    HTTP status code
     * @param-out array<string, string> $httpHeaders HTTP headers
     */
    public function resolve(RouteCase $route, ServerRequestInterface $request, int &$httpCode = 200, array &$httpHeaders = []): StreamInterface|string
    {
        if (! is_http_redirect($httpCode)) {
            $httpCode = 302;
        }

        $address = str_first_line($route->body ?: '');

        // Set Location directly if the address is an external resource
        if ($this->isExternalResource($address)) {
            $httpHeaders['Location'] = $address;
        } else {
            $endpoint = ltrim(substr($address, 11), '/');
            $uri = $request->getUri();
            $host = $uri->getHost();
            $scheme = $uri->getScheme();
            $port = $uri->getPort() ?? 80;
            $port = in_array($port, [80, 443]) ? '' : ":$port";
            $root = ($this->root) ? "/{$this->root}" : '';

            $httpHeaders['Location'] = "{$scheme}://{$host}{$port}{$root}/{$endpoint}";
        }

        return ReasonPhrase::fromInteger($httpCode)->value;
    }

    public function supports(RouteCase $route): bool
    {
        $address = str_first_line($route->body ?: '');

        // Don't resolve content if the route is a redirect
        if (is_http_redirect($route->code ?: 0) && $this->isExternalResource($address)) {
            return true;
        }

        return str_starts_with(strtolower($address), 'redirect://');
    }

    protected function isExternalResource(string $uri): bool
    {
        return (bool) preg_match('/((ht|f)tps?|sftp):\/\//', strtolower($uri));
    }
}
