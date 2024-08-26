<?php

declare(strict_types=1);

namespace Carte\Tests\Unit;

use Carte\Http\Method;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MethodTest extends TestCase
{
    #[Test]
    public function canCreateFromName(): void
    {
        $method = Method::fromName('GET');

        $this->assertInstanceOf(Method::class, $method);
        $this->assertEquals('GET', $method->value);
    }

    #[Test]
    public function canCreateFromAsterisk(): void
    {
        $method = Method::fromName('ANY');

        $this->assertInstanceOf(Method::class, $method);
        $this->assertEquals('*', $method->value);

        $method = Method::fromName('*');

        $this->assertInstanceOf(Method::class, $method);
        $this->assertEquals('*', $method->value);
    }
}
