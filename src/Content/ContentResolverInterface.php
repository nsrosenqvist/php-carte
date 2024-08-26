<?php

declare(strict_types=1);

namespace Carte\Content;

use Carte\Routes\RouteCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

interface ContentResolverInterface
{
    /**
     * @param RouteCase              $route       Matching route
     * @param ServerRequestInterface $request     Request object
     * @param int                    $httpCode    HTTP status code
     * @param array<string, string>  $httpHeaders HTTP headers
     *
     * @param-out int                   $httpCode    HTTP status code
     * @param-out array<string, string> $httpHeaders HTTP headers
     */
    public function resolve(RouteCase $route, ServerRequestInterface $request, int &$httpCode = 200, array &$httpHeaders = []): StreamInterface|string;

    public function supports(RouteCase $route): bool;
}
