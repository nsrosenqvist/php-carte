<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Routes;

use Carte\Routes\RouteGroup;
use Carte\Routes\RouteList;
use Carte\Strategies\Strategy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RouteGroupTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    protected array $definition = [
        'strategy' => Strategy::class,
        'routes' => [
            'foo/baz' => [
                'code' => 404,
                'body' => 'Not Found',
            ],
            'foo/bar' => [
                'code' => 200,
                'body' => 'OK',
            ],
        ],
    ];

    #[Test]
    public function canCreateFromDefinition(): void
    {
        $group = RouteGroup::fromArray('lorem/ipsum', $this->definition);

        $this->assertInstanceOf(RouteGroup::class, $group);
        $this->assertEquals('lorem/ipsum', $group->pattern);
        $this->assertObjectHasProperty('strategy', $group);
        $this->assertEquals($this->definition['strategy'], $group?->strategy);

        $this->assertCount(2, $group->routes->getMap());
    }

    #[Test]
    public function canRecreateDefinition(): void
    {
        $group = RouteGroup::fromArray('foo/bar', $this->definition);
        $array = $group->toArray();

        $this->assertEqualsCanonicalizing($this->definition, $array);
    }

    #[Test]
    public function canFlattenGroup(): void
    {
        $group = RouteGroup::fromArray('lorem/ipsum', $this->definition);
        $flattened = $group->flatten();
        $patternOne = 'lorem/ipsum/foo/bar';
        $patternTwo = 'lorem/ipsum/foo/baz';

        $this->assertCount(2, $flattened);
        $this->assertInstanceOf(RouteList::class, $flattened[$patternOne] ?? null);
        $this->assertInstanceOf(RouteList::class, $flattened[$patternTwo] ?? null);
        $this->assertArrayHasKey(0, $flattened[$patternOne]->getRouteCases());
        $this->assertArrayHasKey(0, $flattened[$patternTwo]->getRouteCases());

        $routeOne = $flattened[$patternOne]->getRouteCases()[0];
        $routeTwo = $flattened[$patternTwo]->getRouteCases()[0];

        $this->assertEquals($patternOne, $routeOne->pattern);
        $this->assertEquals($patternTwo, $routeTwo->pattern);
        $this->assertEquals(Strategy::class, $routeOne->strategy ?? null);
        $this->assertEquals(Strategy::class, $routeTwo->strategy ?? null);
        $this->assertEquals($patternOne, $routeOne->match->pattern);
        $this->assertEquals($patternTwo, $routeTwo->match->pattern);
    }

    #[Test]
    public function canCreateNestedGroups(): void
    {
        $nestedDefinition = $this->definition;
        $nestedDefinition['routes']['foo/baz'] = $this->definition;
        $group = RouteGroup::fromArray('lorem/ipsum', $nestedDefinition);
        $map = $group->routes->getMap();

        $this->assertArrayHasKey('foo/baz', $map);
        $this->assertInstanceOf(RouteGroup::class, $map['foo/baz']);
        $this->assertCount(2, $map['foo/baz']->routes->getMap());
    }

    #[Test]
    public function canRecreateNestedGroups(): void
    {
        $nestedDefinition = $this->definition;
        $nestedDefinition['routes']['foo/baz'] = $this->definition;
        $group = RouteGroup::fromArray('lorem/ipsum', $nestedDefinition);
        $array = $group->toArray();

        $this->assertEqualsCanonicalizing($nestedDefinition, $array);
    }

    #[Test]
    public function canFlattenNestedGroup(): void
    {
        $nestedDefinition = $this->definition;
        $nestedDefinition['routes']['foo/baz'] = $this->definition;
        $group = RouteGroup::fromArray('lorem/ipsum', $nestedDefinition);
        $flattened = $group->flatten();

        $this->assertCount(3, $flattened);
        $this->assertArrayHasKey('lorem/ipsum/foo/bar', $flattened);
        $this->assertArrayHasKey('lorem/ipsum/foo/baz/foo/bar', $flattened);
        $this->assertArrayHasKey('lorem/ipsum/foo/baz/foo/baz', $flattened);
    }

    #[Test]
    public function canCreateWithExtras(): void
    {
        $definition = $this->definition;
        $definition['extras'] = ['foo' => 'bar'];
        $definition['lorem'] = 'ipsum';
        $group = RouteGroup::fromArray('foo/bar', $definition);

        $extras = $group->extras;
        $this->assertCount(2, $extras);
        $this->assertEquals('bar', $extras['foo'] ?? null);
        $this->assertEquals('ipsum', $extras['lorem'] ?? null);

        $array = $group->toArray();
        $this->assertEquals('bar', $array['extras']['foo'] ?? null);
        $this->assertEquals('ipsum', $array['extras']['lorem'] ?? null);

        $flattened = $group->flatten();
        $this->assertArrayHasKey('foo/bar/foo/bar', $flattened);
        $route = $flattened['foo/bar/foo/bar']->getRouteCases()[0]->toArray();
        $this->assertEquals('bar', $route['extras']['foo'] ?? null);
        $this->assertEquals('ipsum', $array['extras']['lorem'] ?? null);
    }
}
