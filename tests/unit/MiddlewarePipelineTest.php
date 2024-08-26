<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Routes;

use Carte\MiddlewarePipeline;
use Carte\Strategies\Strategy;
use Carte\Tests\Lib\MiddlewareOne;
use Carte\Tests\Lib\MiddlewarePostprocess;
use Carte\Tests\Lib\MiddlewarePreprocess;
use Carte\Tests\Lib\MiddlewareThree;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewarePipelineTest extends TestCase implements RequestHandlerInterface
{
    protected bool $preprocessed = false;

    #[Test]
    public function canPreprocessRequest(): void
    {
        $strategy = new Strategy([
            new MiddlewareOne(),
            new MiddlewarePreprocess(),
            new MiddlewareThree(),
        ]);

        $request = ServerRequest::fromGlobals();
        $pipeline = new MiddlewarePipeline($strategy, $this);
        $pipeline->handle($request);

        $this->assertTrue($this->preprocessed);
    }

    #[Test]
    public function canPostprocessResponse(): void
    {
        $strategy = new Strategy([
            new MiddlewareOne(),
            new MiddlewarePostprocess(),
            new MiddlewareThree(),
        ]);

        $request = ServerRequest::fromGlobals();
        $pipeline = new MiddlewarePipeline($strategy, $this);
        $response = $pipeline->handle($request);

        $this->assertTrue((bool) current($response->getHeader('postprocessed')));
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->hasHeader('preprocessed')) {
            $this->preprocessed = true;
        }

        return new Response(200, [], 'Hello World!');
    }
}
