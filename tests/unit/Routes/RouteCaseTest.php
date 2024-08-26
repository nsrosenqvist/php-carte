<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Routes;

use Carte\Http\Method;
use Carte\Routes\RouteCase;
use Carte\Strategies\Strategy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RouteCaseTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    protected array $definition = [
        'code' => 302,
        'body' => 'lorem ipsum',
        'headers' => ['Content-Type' => 'application/json'],
        'version' => '1.1',
        'reason' => 'Moved Permanently',
        'strategy' => Strategy::class,
        'match' => [
            'method' => Method::GET->value,
            'query' => ['foo' => 'bar'],
        ],
    ];

    #[Test]
    public function canCreateFromDefinition(): void
    {
        $case = RouteCase::fromArray('foo/bar', $this->definition);

        $this->assertInstanceOf(RouteCase::class, $case);
        $this->assertEquals('foo/bar', $case->pattern);
        $this->assertEquals($this->definition['code'], $case?->code);
        $this->assertEquals($this->definition['body'], $case?->body);
        $this->assertEquals($this->definition['headers'], $case?->headers);
        $this->assertEquals($this->definition['version'], $case?->version);
        $this->assertEquals($this->definition['reason'], $case?->reason);
        $this->assertEquals($this->definition['strategy'], $case?->strategy);
        $this->assertEquals($this->definition['match']['method'], $case->match?->method->value);
        $this->assertEquals($this->definition['match']['query'], $case->match?->query);
    }

    #[Test]
    public function canRecreateDefinition(): void
    {
        $case = RouteCase::fromArray('foo/bar', $this->definition);
        $array = $case->toArray();

        $this->assertEqualsCanonicalizing($this->definition, $array);
    }

    #[Test]
    public function canCreateWithExtras(): void
    {
        $definition = $this->definition;
        $definition['extras'] = ['foo' => 'bar'];
        $definition['lorem'] = 'ipsum';
        $case = RouteCase::fromArray('foo/bar', $definition);

        $extras = $case->extras;
        $this->assertCount(2, $extras);
        $this->assertEquals('bar', $extras['foo'] ?? null);
        $this->assertEquals('ipsum', $extras['lorem'] ?? null);

        $array = $case->toArray();
        $this->assertEquals('bar', $array['extras']['foo'] ?? null);
        $this->assertEquals('ipsum', $array['extras']['lorem'] ?? null);
    }
}
