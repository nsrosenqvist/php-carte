<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Routes;

use Carte\Routes\RouteMap;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RouteMapTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    protected array $definition = [
        'foo/baz' => [
            'code' => 404,
            'body' => 'Not Found',
        ],
        'foo/bar' => [
            'code' => 200,
            'body' => 'OK',
        ],
    ];

    /**
     * @var array<string>
     */
    protected array $patternsOrder = [
        'route/specificity/200',
        'route/{detail}/100',
        'route/specificity/{id}',
        'route/specificity/*',
        'route/{detail}/*',
        'route/specificity',
        'route/{detail}',
        'route/*',
    ];

    #[Test]
    public function canCreateFromDefinition(): void
    {
        $map = RouteMap::fromArray($this->definition);
        $entries = $map->getMap();

        $this->assertInstanceOf(RouteMap::class, $map);
        $this->assertArrayHasKey('foo/baz', $entries);
        $this->assertArrayHasKey('foo/bar', $entries);
    }

    #[Test]
    public function canRecreateDefinition(): void
    {
        $map = RouteMap::fromArray($this->definition);
        $array = $map->toArray();

        $this->assertEqualsCanonicalizing($this->definition, $array);
    }

    #[Test]
    public function canOrderAccordingToSpecificity(): void
    {
        // Enforce other order than the original
        $shuffled = $this->patternsOrder;

        while ($shuffled === $this->patternsOrder) {
            shuffle($shuffled);
        }

        $codes = array_fill(0, count($shuffled), 200);
        $shuffled = array_combine($shuffled, $codes);

        $map = RouteMap::fromArray($shuffled);
        $array = $map->toArray();

        $this->assertEquals($this->patternsOrder, array_keys($array));
    }
}
