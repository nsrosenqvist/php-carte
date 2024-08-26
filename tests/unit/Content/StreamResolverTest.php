<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Routes;

use Carte\Content\ContentResolverInterface;
use Carte\Content\StreamResolver;
use Carte\Routes\RouteCase;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class StreamResolverTest extends TestCase
{
    protected static string $root = __DIR__ . '/../../resources/content';

    protected static ContentResolverInterface $resolver;

    protected static ServerRequest $request;

    #[BeforeClass]
    public static function initialize(): void
    {
        $factory = new HttpFactory();
        static::$resolver = new StreamResolver(static::$root, $factory);
        static::$request = ServerRequest::fromGlobals();
    }

    #[Test]
    public function canResolveStream(): void
    {
        $code = 200;
        $headers = [];
        $route = new RouteCase(pattern: '', body: 'stream://plain.txt');
        $stream = static::$resolver->resolve($route, static::$request, $code, $headers);

        $root = static::$root;
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertEquals(file_get_contents("{$root}/plain.txt"), (string) $stream);
    }

    #[Test]
    public function canMatchUrlPattern(): void
    {
        $stream = new RouteCase(pattern: '', body: 'stream://plain.txt');
        $php = new RouteCase(pattern: '', body: 'php://handler.php');
        $this->assertTrue(static::$resolver->supports($stream));
        $this->assertFalse(static::$resolver->supports($php));
    }
}
