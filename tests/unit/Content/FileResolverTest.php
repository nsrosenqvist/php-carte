<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Routes;

use Carte\Content\ContentResolverInterface;
use Carte\Content\FileResolver;
use Carte\Routes\RouteCase;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FileResolverTest extends TestCase
{
    protected static string $root = __DIR__ . '/../../resources/content';

    protected static ContentResolverInterface $resolver;

    protected static ServerRequest $request;

    #[BeforeClass]
    public static function initialize(): void
    {
        static::$resolver = new FileResolver(static::$root);
        static::$request = ServerRequest::fromGlobals();
    }

    #[Test]
    public function canResolveJson(): void
    {
        $code = 200;
        $headers = [];
        $route = new RouteCase(pattern: '', body: 'file://json.json');
        $json = static::$resolver->resolve($route, static::$request, $code, $headers);

        $root = static::$root;
        $this->assertEquals(file_get_contents("{$root}/json.json"), $json);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals('application/json', $headers['Content-Type']);
    }

    #[Test]
    public function canResolveText(): void
    {
        $code = 200;
        $headers = [];
        $route = new RouteCase(pattern: '', body: 'file://plain.txt');
        $text = static::$resolver->resolve($route, static::$request, $code, $headers);

        $root = static::$root;
        $this->assertEquals(file_get_contents("{$root}/plain.txt"), $text);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals($headers['Content-Type'], 'text/plain');
    }

    #[Test]
    public function canMatchUrlPattern(): void
    {
        $file = new RouteCase(pattern: '', body: 'file://json.json');
        $php = new RouteCase(pattern: '', body: 'php://handler.php');
        $this->assertTrue(static::$resolver->supports($file));
        $this->assertFalse(static::$resolver->supports($php));
    }
}
