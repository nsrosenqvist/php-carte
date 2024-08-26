<?php

declare(strict_types=1);

namespace Carte\Tests\Unit;

use Carte\Strategies\Strategy;
use Carte\Tests\Lib\MiddlewareOne;
use Carte\Tests\Lib\MiddlewareThree;
use Carte\Tests\Lib\MiddlewareTwo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StrategyTest extends TestCase
{
    #[Test]
    public function canCreateWithMiddleware(): void
    {
        $strategy = new Strategy($middlewares = [
            new MiddlewareOne(),
            new MiddlewareTwo(),
        ]);

        $this->assertEquals($middlewares, $strategy->getMiddlewares());
    }

    #[Test]
    public function canPushMiddleware(): void
    {
        $strategy = new Strategy([
            new MiddlewareOne(),
            new MiddlewareTwo(),
        ]);

        $strategy->pushMiddleware(new MiddlewareThree());

        $this->assertCount(3, $strategy->getMiddlewares());
        $this->assertInstanceOf(MiddlewareThree::class, $strategy->getMiddlewares()[2]);
    }

    #[Test]
    public function canUnshiftMiddleware(): void
    {
        $strategy = new Strategy([
            new MiddlewareOne(),
            new MiddlewareTwo(),
        ]);

        $strategy->unshiftMiddleware(new MiddlewareThree());

        $this->assertCount(3, $strategy->getMiddlewares());
        $this->assertInstanceOf(MiddlewareThree::class, $strategy->getMiddlewares()[0]);
    }
}
