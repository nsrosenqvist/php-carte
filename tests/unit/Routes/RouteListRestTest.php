<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Routes;

use Carte\Http\Method;
use Carte\Routes\RouteCase;
use Carte\Routes\RouteListRest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RouteListRestTest extends TestCase
{
    /**
     * @var array<string, int|string>
     */
    protected array $definition = [
        Method::GET->value => 'body',
        Method::POST->value => 204,
        Method::ANY->value => 404,
        Method::PUT->value => [
            'code' => 302,
            'body' => 'http://foo.bar/',
        ],
    ];

    #[Test]
    public function canCreateFromDefinition(): void
    {
        $list = RouteListRest::fromArray('foo/bar', $this->definition);
        $cases = $list->getRouteCases();

        $this->assertInstanceOf(RouteListRest::class, $list);
        $this->assertCount(4, $cases);
        $this->assertInstanceOf(RouteCase::class, current($cases));
        $this->assertEquals('foo/bar', $cases[0]->pattern);
        $this->assertEquals('body', $cases[0]->body);
        $this->assertEquals('foo/bar', $cases[1]->pattern);
        $this->assertEquals(204, $cases[1]->code);
        $this->assertEquals('foo/bar', $cases[2]->pattern);
        $this->assertEquals(404, $cases[2]->code);
        $this->assertEquals('foo/bar', $cases[3]->pattern);
        $this->assertEquals(302, $cases[3]->code);
        $this->assertEquals('http://foo.bar/', $cases[3]->body);
    }

    #[Test]
    public function canRecreateDefinition(): void
    {
        $list = RouteListRest::fromArray('foo/bar', $this->definition);
        $array = $list->toArray();

        $this->assertEquals($this->definition, $array);
    }
}
