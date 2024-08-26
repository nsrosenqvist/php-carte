<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Routes;

use Carte\Exceptions\RouteNotFoundException;
use Carte\Manifest;
use Carte\Router;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    protected static HttpFactory $factory;

    protected static Manifest $manifest;

    protected static Router $router;

    #[BeforeClass]
    public static function defineRouter(): void
    {
        static::$factory = new HttpFactory();
        static::$manifest = new Manifest(__DIR__ . '/../resources/manifests/site.yml');
        static::$router = new Router(
            responseFactory: static::$factory,
            streamFactory: static::$factory,
            manifest: static::$manifest,
        );
    }

    #[Test]
    public function canFailGracefully(): void
    {
        $request = static::$factory->createServerRequest('GET', '/unknown');
        $router = clone static::$router;

        $response = $router->setFailGracefully(true)->dispatch($request);
        $this->assertEquals(404, $response->getStatusCode());

        $this->expectException(RouteNotFoundException::class);
        $response = $router->setFailGracefully(false)->dispatch($request);
    }

    #[Test]
    public function canReturnDefaultResponse(): void
    {
        $default = static::$factory->createResponse(200, 'Default response');
        $request = static::$factory->createServerRequest('GET', '/unknown');

        $router = (clone static::$router)->setFailGracefully(false);
        $response = $router->setDefaultResponse($default)->dispatch($request);
        $this->assertEquals('Default response', $response->getReasonPhrase());

        $this->expectException(RouteNotFoundException::class);
        $response = $router->setDefaultResponse(null)->dispatch($request);
    }

    #[Test]
    public function canMatchIndex(): void
    {
        $request = static::$factory->createServerRequest('GET', '/');
        $response = static::$router->dispatch($request);

        $this->assertEquals('index', $response->getBody()->getContents());
    }

    #[Test]
    public function canMatchGroupIndex(): void
    {
        $request = static::$factory->createServerRequest('GET', '/blog/');
        $response = static::$router->dispatch($request);

        $this->assertEquals('blog:index', $response->getBody()->getContents());
    }

    #[Test]
    public function canMatchVariable(): void
    {
        $request = static::$factory->createServerRequest('GET', '/archive/lorem/ipsum/2');
        $response = static::$router->dispatch($request);

        $this->assertEquals('archive:2', $response->getBody()->getContents());
    }

    #[Test]
    public function canMatchQuery(): void
    {
        $request = static::$factory->createServerRequest('GET', '/archive/lorem/ipsum/1');
        $request = $request->withQueryParams(['foo' => 'bar']);
        $response = static::$router->dispatch($request);

        $this->assertEquals('archive:1:query', $response->getBody()->getContents());
    }

    #[Test]
    public function canMatchEmptyWildcard(): void
    {
        $request = static::$factory->createServerRequest('GET', '/archive/lorem/ipsum');
        $response = static::$router->dispatch($request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function canMatchPopulatedWildcard(): void
    {
        $request = static::$factory->createServerRequest('GET', '/archive/lorem/ipsum/4');
        $response = static::$router->dispatch($request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function canMatchMethod(): void
    {
        $request = static::$factory->createServerRequest('POST', '/contact');
        $response = static::$router->dispatch($request);

        $this->assertEquals('contact:post', $response->getBody()->getContents());

        $request = static::$factory->createServerRequest('GET', '/contact');
        $response = static::$router->dispatch($request);
        $this->assertEquals('contact:get', $response->getBody()->getContents());
    }

    #[Test]
    public function canResolveRoutesWithRootUri(): void
    {
        $request = static::$factory->createServerRequest('POST', 'root/contact');
        $router = (clone static::$router)->setRootUri('root');
        $response = $router->dispatch($request);

        $this->assertEquals('contact:post', $response->getBody()->getContents());
    }
}
