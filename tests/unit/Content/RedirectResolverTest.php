<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Routes;

use Alexanderpas\Common\HTTP\ReasonPhrase;
use Carte\Content\RedirectResolver;
use Carte\Routes\RouteCase;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RedirectResolverTest extends TestCase
{
    protected static ServerRequest $request;

    #[BeforeClass]
    public static function initialize(): void
    {
        static::$request = ServerRequest::fromGlobals();
    }

    #[Test]
    public function canResolveRedirect(): void
    {
        $code = 200;
        $headers = [];
        $route = new RouteCase(pattern: '', body: 'redirect://foo/bar');
        $body = (new RedirectResolver())->resolve($route, static::$request, $code, $headers);

        $this->assertEquals(ReasonPhrase::fromInteger(302)->value, $body);
        $this->assertArrayHasKey('Location', $headers);
        $this->assertEquals('http://localhost/foo/bar', $headers['Location']);
        $this->assertEquals(302, $code);
    }

    #[Test]
    public function canResolveRootRedirect(): void
    {
        $code = 200;
        $headers = [];
        $route = new RouteCase(pattern: '', body: 'redirect://foo/bar');
        $body = (new RedirectResolver('root'))->resolve($route, static::$request, $code, $headers);

        $this->assertEquals(ReasonPhrase::fromInteger(302)->value, $body);
        $this->assertArrayHasKey('Location', $headers);
        $this->assertEquals('http://localhost/root/foo/bar', $headers['Location']);
        $this->assertEquals(302, $code);
    }

    #[Test]
    public function canResolveExternalRedirect(): void
    {
        $code = 200;
        $headers = [];
        $route = new RouteCase(pattern: '', code: 302, body: 'http://foo.bar/');
        $body = (new RedirectResolver())->resolve($route, static::$request, $code, $headers);

        $this->assertEquals(ReasonPhrase::fromInteger(302)->value, $body);
        $this->assertArrayHasKey('Location', $headers);
        $this->assertEquals('http://foo.bar/', $headers['Location']);
        $this->assertEquals(302, $code);
    }

    #[Test]
    public function canMatchUrlPattern(): void
    {
        $internal = new RouteCase(pattern: '', body: 'redirect://foo/bar');
        $external = new RouteCase(pattern: '', code: 302, body: 'http://foo.bar/');
        $php = new RouteCase(pattern: '', body: 'php://handler.php');
        $this->assertTrue((new RedirectResolver())->supports($internal));
        $this->assertTrue((new RedirectResolver())->supports($external));
        $this->assertFalse((new RedirectResolver())->supports($php));
    }
}
