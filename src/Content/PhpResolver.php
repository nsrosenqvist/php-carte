<?php

declare(strict_types=1);

namespace Carte\Content;

use Carte\Content\ContentResolverInterface;
use Carte\Exceptions\FileNotFoundException;
use Carte\Routes\RouteCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

use function Carte\str_first_line;

class PhpResolver implements ContentResolverInterface
{
    /**
     * @throws FileNotFoundException
     */
    public function __construct(
        protected string $sourceDirectory,
    ) {
        $this->sourceDirectory = realpath($this->sourceDirectory) ?: '';

        if (! $this->sourceDirectory || ! is_dir($this->sourceDirectory)) {
            throw new FileNotFoundException("Resource directory not found: $sourceDirectory");
        }
    }

    /**
     * @param RouteCase              $route       Matching route
     * @param ServerRequestInterface $request     Request object
     * @param int                    $httpCode    HTTP status code
     * @param array<string, string>  $httpHeaders HTTP headers
     *
     * @throws FileNotFoundException
     *
     * @param-out int                   $httpCode    HTTP status code
     * @param-out array<string, string> $httpHeaders HTTP headers
     */
    public function resolve(RouteCase $route, ServerRequestInterface $request, int &$httpCode = 200, array &$httpHeaders = []): StreamInterface|string
    {
        $file = substr($route->body ?: '', 6);
        $path = "{$this->sourceDirectory}/$file";

        if (! str_starts_with(realpath($path) ?: '', $this->sourceDirectory)) {
            throw new FileNotFoundException("File not found: $file");
        }

        ob_start();
        require $path;

        return ob_get_clean() ?: '';
    }

    public function supports(RouteCase $route): bool
    {
        return str_starts_with(strtolower(str_first_line($route->body ?: '')), 'php://');
    }
}
