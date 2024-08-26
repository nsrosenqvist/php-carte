<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Routes;

use Carte\Http\Method;
use Carte\Routes\RouteCaseShort;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RouteCaseShortTest extends TestCase
{
    #[Test]
    public function canCreateFromDefinition(): void
    {
        $case = RouteCaseShort::fromArray('foo/bar', [302]);

        $this->assertInstanceOf(RouteCaseShort::class, $case);
        $this->assertEquals('foo/bar', $case->pattern);
        $this->assertEquals(302, $case?->code);
    }

    #[Test]
    public function canRecreateDefinition(): void
    {
        $case = RouteCaseShort::fromArray('foo/bar', [302]);
        $array = $case->toArray();

        $this->assertEquals(302, current($array));
        $this->assertCount(1, $array);
    }

    #[Test]
    public function canCreateWithBody(): void
    {
        $body = 'lorem ipsum';
        $case = RouteCaseShort::fromArray('foo/bar', [$body]);
        $array = $case->toArray();

        $this->assertEquals($body, current($array));
    }

    #[Test]
    public function canCreateWithMethod(): void
    {
        $method = Method::PUT;
        $case = RouteCaseShort::fromArray('foo/bar', [$method->value]);
        $array = $case->toArray();

        $this->assertEquals($method->value, current($array));
    }
}
