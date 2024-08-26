<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Routes;

use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RouterIntegrationTest extends TestCase
{
    protected static int $pid;

    protected static Client $client;

    protected static string $resolverRoot = __DIR__ . '/../resources/content';

    #[BeforeClass]
    public static function setupServer(): void
    {
        $serverRoot = realpath(__DIR__ . '/../resources/server');
        $command = sprintf('php -S localhost:8000 -t %s > /dev/null 2>&1 & echo $!', escapeshellarg($serverRoot));

        pcntl_signal(SIGCHLD, SIG_IGN);
        static::$pid = (int) exec($command);
        static::$client = new Client([
            'base_uri' => 'http://localhost:8000',
            'http_errors' => false,
            'allow_redirects' => true,
            'timeout' => 3,
        ]);
    }

    #[AfterClass]
    public static function stopServer(): void
    {
        exec(sprintf('kill -9 %d', static::$pid));
    }

    #[Test]
    public function canHandleIndex(): void
    {
        $response = static::$client->get('/');
        $this->assertEquals('index', $response->getBody()->getContents());
    }

    #[Test]
    public function canHandleNotFound(): void
    {
        $response = static::$client->get('/unknown');
        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function canHandleNestedRoutes(): void
    {
        $response = static::$client->get('/blog/');
        $this->assertEquals('blog:index', $response->getBody()->getContents());
        $response = static::$client->get('/blog/home');
        $this->assertEquals('blog:home', $response->getBody()->getContents());
    }

    #[Test]
    public function canHandleVariableMatching(): void
    {
        $response = static::$client->get('/archive/lorem/ipsum/2');
        $this->assertEquals('archive:2', $response->getBody()->getContents());
        $response = static::$client->get('/archive/lorem/ipsum/3');
        $this->assertEquals('archive:3', $response->getBody()->getContents());
    }

    #[Test]
    public function canHandleQueryMatching(): void
    {
        $response = static::$client->get('/archive/lorem/ipsum/1?foo=bar');
        $this->assertEquals('archive:1:query', $response->getBody()->getContents());
    }

    #[Test]
    public function canHandleWildcardMatching(): void
    {
        $response = static::$client->get('/archive/lorem/ipsum/*');
        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function canHandleMethodMatching(): void
    {
        $response = static::$client->get('/contact');
        $this->assertEquals('contact:get', $response->getBody()->getContents());
        $response = static::$client->post('/contact');
        $this->assertEquals('contact:post', $response->getBody()->getContents());
    }

    #[Test]
    public function canResolveRedirects(): void
    {
        $response = static::$client->get('/resolver/redirect');
        $this->assertEquals('blog:home', $response->getBody()->getContents());
    }

    #[Test]
    public function canResolveFiles(): void
    {
        $response = static::$client->get('/resolver/file');
        $headers = $response->getHeaders();

        $this->assertEquals(file_get_contents(static::$resolverRoot . '/json.json'), $response->getBody()->getContents());
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals('application/json', current($headers['Content-Type']));
    }

    #[Test]
    public function canResolvePhp(): void
    {
        $response = static::$client->get('/resolver/php');
        $headers = $response->getHeaders();
        $headers = array_combine(
            array_map('strtolower', array_keys($headers)),
            array_values($headers),
        );

        $this->assertEquals('Hello World!', $response->getBody()->getContents());
        $this->assertArrayHasKey('content-type', $headers);
        $this->assertStringContainsString('text/plain', current($headers['content-type']));
        $this->assertEquals(202, $response->getStatusCode());
    }
}
