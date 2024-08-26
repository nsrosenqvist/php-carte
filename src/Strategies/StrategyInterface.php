<?php

declare(strict_types=1);

namespace Carte\Strategies;

use Psr\Http\Server\MiddlewareInterface;

interface StrategyInterface
{
    public function pushMiddleware(MiddlewareInterface ...$middlewares): static;

    public function unshiftMiddleware(MiddlewareInterface ...$middlewares): static;

    /**
     * @return MiddlewareInterface[]
     */
    public function getMiddlewares(): array;
}
