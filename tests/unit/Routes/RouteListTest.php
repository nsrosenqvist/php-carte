<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Routes;

use Carte\Http\Method;
use Carte\Routes\RouteCase;
use Carte\Routes\RouteList;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RouteListTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    protected array $definition = [
        [
            'body' => 'method',
            'match' => [
                'method' => Method::PUT->value,
                'query' => [
                    'foo' => 'bar',
                ],
            ],
        ],
        [
            'body' => 'query-more',
            'match' => [
                'query' => [
                    'foo' => 'bar',
                    'lorem' => 'ipsum',
                ],
            ],
        ],
        [
            'body' => 'query-less',
            'match' => [
                'query' => [
                    'foo' => 'bar',
                ],
            ],
        ],
        [
            'body' => 'empty',
        ],
    ];

    /**
     * @var array<string, mixed>
     */
    protected array $single = [
        'code' => 200,
        'body' => 'OK',
    ];

    #[Test]
    public function canCreateFromDefinition(): void
    {
        $list = RouteList::fromArray('foo/bar', $this->definition);
        $cases = $list->getRouteCases();

        $this->assertInstanceOf(RouteList::class, $list);
        $this->assertCount(4, $cases);
        $this->assertInstanceOf(RouteCase::class, current($cases));
    }

    #[Test]
    public function canRecreateDefinition(): void
    {
        $list = RouteList::fromArray('foo/bar', $this->definition);
        $array = $list->toArray();

        $this->assertEquals($this->definition, $array);
    }

    #[Test]
    public function canCreateFromSingleDefinition(): void
    {
        $list = RouteList::fromArray('foo/bar', $this->single);
        $cases = $list->getRouteCases();

        $this->assertCount(1, $cases);
        $this->assertInstanceOf(RouteCase::class, current($cases));
    }

    #[Test]
    public function canRecreateSingleDefinition(): void
    {
        $list = RouteList::fromArray('foo/bar', $this->single);
        $array = $list->toArray();

        $this->assertEqualsCanonicalizing($this->single, $array);
    }

    #[Test]
    public function canOrderAccordingToSpecificity(): void
    {
        // Enforce other order than the original
        $shuffled = $this->definition;

        while ($shuffled === $this->definition) {
            shuffle($shuffled);
        }

        $list = RouteList::fromArray('foo/bar', $shuffled);
        $array = $list->toArray();

        $this->assertEquals($this->definition, $array);
    }
}
