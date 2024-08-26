<?php

declare(strict_types=1);

namespace Carte\Content;

use Carte\Content\ContentResolverInterface;
use Carte\Exceptions\FileNotFoundException;
use Carte\Routes\RouteCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

use function Carte\str_first_line;

class FileResolver implements ContentResolverInterface
{
    /**
     * @throws FileNotFoundException
     */
    public function __construct(
        protected string $resourceDirectory,
    ) {
        $this->resourceDirectory = realpath($this->resourceDirectory) ?: '';

        if (! $this->resourceDirectory || ! is_dir($this->resourceDirectory)) {
            throw new FileNotFoundException("Resource directory not found: $resourceDirectory");
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
        $file = substr($route->body ?: '', 7);
        $path = "{$this->resourceDirectory}/$file";

        if (! str_starts_with(realpath($path) ?: '', $this->resourceDirectory)) {
            throw new FileNotFoundException("File not found: $file");
        }

        $body = file_get_contents($path);

        if ($body === false) {
            throw new FileNotFoundException("File could not be read: $file");
        }

        if (! isset($httpHeaders['Content-Type'])) {
            $httpHeaders['Content-Type'] = mime_content_type($path) ?: null;
        }

        return $body;
    }

    public function supports(RouteCase $route): bool
    {
        return str_starts_with(strtolower(str_first_line($route->body ?: '')), 'file://');
    }
}
