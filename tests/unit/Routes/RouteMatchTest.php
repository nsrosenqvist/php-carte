<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Routes;

use Carte\Http\Method;
use Carte\Routes\RouteMatch;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RouteMatchTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    protected array $definition = [
        'method' => Method::GET->value,
        'query' => [
            'foo' => 'bar',
            'bool' => '__true__',
        ],
        'lorem' => 'ipsum',
    ];

    #[Test]
    public function canCreateFromDefinition(): void
    {
        $match = RouteMatch::fromArray('foo/bar', $this->definition);

        $this->assertInstanceOf(RouteMatch::class, $match);
        $this->assertEquals('foo/bar', $match->pattern);
        $this->assertEquals($this->definition['method'], $match?->method->value);
        $this->assertEquals($this->definition['query'], $match?->query);
        $this->assertEquals($this->definition['lorem'], $match?->variables['lorem'] ?? null);
    }

    #[Test]
    public function canRecreateDefinition(): void
    {
        $match = RouteMatch::fromArray('foo/bar', $this->definition);
        $array = $match->toArray();

        $this->assertEqualsCanonicalizing($this->definition, $array);
    }

    #[Test]
    public function canMatchPattern(): void
    {
        $match = RouteMatch::fromArray('foo/bar', []);

        $this->assertTrue($match->evaluate('foo/bar', Method::GET, []));
        $this->assertFalse($match->evaluate('foo/baz', Method::GET, []));
    }

    #[Test]
    public function canMatchPatternVariable(): void
    {
        $match = RouteMatch::fromArray('foo/{var}/bar', [
            'var' => 'baz',
        ]);

        $this->assertTrue($match->evaluate('foo/baz/bar', Method::GET, []));
        $this->assertFalse($match->evaluate('foo/bad/bar', Method::GET, []));
    }

    #[Test]
    public function canMatchPatternWildcard(): void
    {
        $match = RouteMatch::fromArray('foo/*/bar/*', []);

        $this->assertTrue($match->evaluate('foo/bad/bar/bat', Method::GET, []));
        $this->assertTrue($match->evaluate('foo/bad/bar/bat/ban', Method::GET, []));
        $this->assertFalse($match->evaluate('foo/baz/bar', Method::GET, []));
    }

    #[Test]
    public function canMatchPatternCombination(): void
    {
        $match = RouteMatch::fromArray('foo/*/{var}', [
            'var' => 'bar',
        ]);

        $this->assertTrue($match->evaluate('foo/bad/bar', Method::GET, []));
        $this->assertFalse($match->evaluate('foo/bad/bat', Method::GET, []));
    }

    #[Test]
    public function canMatchMethodConditions(): void
    {
        $match = RouteMatch::fromArray('foo/bar', [
            'method' => Method::GET->value,
        ]);

        $this->assertTrue($match->evaluate('foo/bar', Method::GET, []));
        $this->assertFalse($match->evaluate('foo/bar', Method::POST, []));
    }

    #[Test]
    public function canMatchQueryConditions(): void
    {
        $match = RouteMatch::fromArray('foo/bar', [
            'query' => [
                'foo' => 'bar',
            ],
        ]);

        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => 'bar']));
        $this->assertFalse($match->evaluate('foo/bar', Method::GET, ['foo' => 'baz']));
        $this->assertFalse($match->evaluate('foo/bar', Method::GET, []));
    }

    #[Test]
    public function canMatchAdvancedQueryConditions(): void
    {
        $match = RouteMatch::fromArray('foo/bar', ['query' => ['foo' => '__isset__']]);
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => 'bar']));
        $this->assertFalse($match->evaluate('foo/bar', Method::GET, []));

        $match = RouteMatch::fromArray('foo/bar', ['query' => ['foo' => '__missing__']]);
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, []));
        $this->assertFalse($match->evaluate('foo/bar', Method::GET, ['foo' => 'bar']));

        $match = RouteMatch::fromArray('foo/bar', ['query' => ['foo' => '__true__']]);
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => '1']));
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => true]));
        $this->assertFalse($match->evaluate('foo/bar', Method::GET, ['foo' => '0']));
        $this->assertFalse($match->evaluate('foo/bar', Method::GET, ['foo' => false]));

        $match = RouteMatch::fromArray('foo/bar', ['query' => ['foo' => '__false__']]);
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => '0']));
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => false]));
        $this->assertFalse($match->evaluate('foo/bar', Method::GET, ['foo' => '1']));
        $this->assertFalse($match->evaluate('foo/bar', Method::GET, ['foo' => true]));

        $match = RouteMatch::fromArray('foo/bar', ['query' => ['foo' => '__bool__']]);
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => 'yes']));
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => 'y']));
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => '1']));
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => 'true']));
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => true]));
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => 'no']));
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => 'n']));
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => '0']));
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => 'false']));
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => false]));

        $match = RouteMatch::fromArray('foo/bar', ['query' => ['foo' => '__string__']]);
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => 'bar']));
        $this->assertFalse($match->evaluate('foo/bar', Method::GET, ['foo' => 1]));

        $match = RouteMatch::fromArray('foo/bar', ['query' => ['foo' => '__numeric__']]);
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => '1']));
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => '1.23']));
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => 1]));
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => 1.23]));
        $this->assertFalse($match->evaluate('foo/bar', Method::GET, ['foo' => 'bar']));

        $match = RouteMatch::fromArray('foo/bar', ['query' => ['foo' => '__int__']]);
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => 123]));
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => '123']));
        $this->assertFalse($match->evaluate('foo/bar', Method::GET, ['foo' => 1.23]));

        $match = RouteMatch::fromArray('foo/bar', ['query' => ['foo' => '__float__']]);
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => 1.23]));
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => '1.23']));
        $this->assertFalse($match->evaluate('foo/bar', Method::GET, ['foo' => 123]));

        $match = RouteMatch::fromArray('foo/bar', ['query' => ['foo' => '__array__']]);
        $this->assertTrue($match->evaluate('foo/bar', Method::GET, ['foo' => [1, 2, 3]]));
        $this->assertFalse($match->evaluate('foo/bar', Method::GET, ['foo' => 123]));
    }
}
