<?php

declare(strict_types=1);

namespace Carte;

use Carte\Strategies\StrategyInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewarePipeline implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    protected array $middlewares;

    public function __construct(
        protected StrategyInterface $strategy,
        protected RequestHandlerInterface $router,
    ) {
        $this->middlewares = $strategy->getMiddlewares();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = array_shift($this->middlewares);

        if ($middleware === null) {
            return $this->router->handle($request);
        }

        return $middleware->process($request, $this);
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }
}
