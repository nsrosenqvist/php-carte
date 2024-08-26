<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Routes;

use Carte\Content\ContentResolverInterface;
use Carte\Content\PhpResolver;
use Carte\Routes\RouteCase;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PhpResolverTest extends TestCase
{
    protected static string $root = __DIR__ . '/../../resources/content';

    protected static ContentResolverInterface $resolver;

    protected static ServerRequest $request;

    #[BeforeClass]
    public static function initialize(): void
    {
        static::$resolver = new PhpResolver(static::$root);
        static::$request = ServerRequest::fromGlobals();
    }

    #[Test]
    public function canResolvePhp(): void
    {
        $code = 200;
        $headers = [];
        $route = new RouteCase(pattern: '', body: 'php://handler.php');
        $body = static::$resolver->resolve($route, static::$request, $code, $headers);

        $this->assertEquals('Hello World!', $body);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals('text/plain', $headers['Content-Type']);
        $this->assertEquals(202, $code);
    }

    #[Test]
    public function canMatchUrlPattern(): void
    {
        $php = new RouteCase(pattern: '', body: 'php://handler.php');
        $file = new RouteCase(pattern: '', body: 'file://json.json');
        $this->assertTrue(static::$resolver->supports($php));
        $this->assertFalse(static::$resolver->supports($file));
    }
}
