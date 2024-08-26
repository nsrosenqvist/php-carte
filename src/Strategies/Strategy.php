<?php

declare(strict_types=1);

namespace Carte\Strategies;

use Carte\Strategies\StrategyInterface;
use Psr\Http\Server\MiddlewareInterface;

class Strategy implements StrategyInterface
{
    /**
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(
        protected array $middlewares = [],
    ) {} // phpcs:ignore

    public function pushMiddleware(MiddlewareInterface ...$middlewares): static
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);

        return $this;
    }

    public function unshiftMiddleware(MiddlewareInterface ...$middlewares): static
    {
        $this->middlewares = array_merge($middlewares, $this->middlewares);

        return $this;
    }

    /**
     * @return MiddlewareInterface[]
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
