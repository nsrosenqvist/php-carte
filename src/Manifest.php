<?php

declare(strict_types=1);

namespace Carte;

use ArrayAccess;
use ArrayIterator;
use Carte\Exceptions\FileNotFoundException;
use Carte\Parsers\Json;
use Carte\Parsers\Jsonc;
use Carte\Parsers\ParserInterface;
use Carte\Parsers\Php;
use Carte\Parsers\Yaml;
use Carte\Routes\RouteCase;
use Carte\Routes\RouteMap;
use IteratorAggregate;
use SplFileObject;
use Traversable;
use ValueError;

use function Carte\is_assoc;

/**
 * @phpstan-import-type RouteMapDefinition from RouteMap
 * @phpstan-import-type RouteCaseDefinition from RouteCase
 * @implements ArrayAccess<string, array<int, RouteCaseDefinition>>
 * @implements IteratorAggregate<string, array<int, RouteCaseDefinition>>
 */
class Manifest implements ArrayAccess, IteratorAggregate
{
    /**
     * @var array<string, array<int, RouteCaseDefinition>>
     */
    protected array $data;

    /**
     * @param SplFileObject|RouteMapDefinition|string $resource  Data structure itself or the path to the file defining it
     * @param SplFileObject|string|null               $cachePath Path to cache file
     *
     * @throws FileNotFoundException
     */
    public function __construct(
        SplFileObject|string|array $resource,
        SplFileObject|string|null $cachePath = null,
    ) {
        // Normalize paths
        if ($cachePath instanceof SplFileObject) {
            $cachePath = $cachePath->getRealPath() ?: null;
        }

        if ($resource instanceof SplFileObject) {
            $resource = $resource->getRealPath() ?: null;
        }

        if ($resource === null || (is_string($resource) && ! @file_exists($resource))) {
            throw new FileNotFoundException('Manifest file not found');
        }

        // Load from cache if exists
        if ($cachePath && @file_exists($cachePath)) {
            $this->data = require $cachePath;
            return;
        }

        $raw = $this->loadManifest($resource);
        $map = new RouteMap($raw);
        $this->data = $map->dump();

        // Cache manifest
        if ($cachePath) {
            file_put_contents($cachePath, '<?php return ' . var_export($this->data, true) . ';');
        }
    }

    /**
     * @param SplFileObject|RouteMapDefinition|string $resource Data structure itself or the path to the file defining it
     *
     * @return RouteMapDefinition
     *
     * @throws FileNotFoundException
     * @throws ValueError
     */
    protected function loadManifest(SplFileObject|array|string $resource): array
    {
        // SplFileObjects and strings define a filesystem path
        if ($resource instanceof SplFileObject) {
            $resource = $resource->getRealPath();
        }

        if (is_string($resource)) {
            $resource = trim($resource);

            // Filesystem path
            if (! @file_exists($resource)) {
                throw new FileNotFoundException("Manifest file not found: {$resource}");
            }

            $format = pathinfo($resource, PATHINFO_EXTENSION);
            $parser = $this->getParser($format);

            if (! $parser) {
                throw new ValueError("Unsupported manifest format: {$format}");
            }

            return $parser->parse($resource);
        }

        // Associative arrays are the default
        if (! is_array($resource) || ! is_assoc($resource)) {
            throw new ValueError('Manifest is of invalid type' . gettype($resource));
        }

        return $resource;
    }

    /**
     * Get manifest parser from format extension
     */
    protected function getParser(string $format): ?ParserInterface
    {
        return match (strtolower($format)) {
            'json' => new Json(),
            'jsonc' => new Jsonc(),
            'php' => new Php(),
            'yml', 'yaml' => new Yaml(),
            default => null,
        };
    }

    /**
     * @return Traversable<string, array<int, RouteCaseDefinition>>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data); // @phpstan-ignore-line
    }

    /**
     * Get manifest definition by route pattern
     *
     * @return array<int, RouteCaseDefinition>
     */
    public function get(string $route): array
    {
        return $this->data[ltrim($route, '/')];
    }

    /**
     * Check if route exists
     */
    public function has(string $route): bool
    {
        return isset($this->data[ltrim($route, '/')]);
    }

    /**
     * @return array<int, RouteCaseDefinition>
     */
    public function __get(string $route): array
    {
        return $this->get($route);
    }

    public function __isset(string $route): bool
    {
        return $this->has($route);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new ValueError(static::class . ' is read-only');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new ValueError(static::class . ' is read-only');
    }

    /**
     * @param string $offset
     *
     * @return array<int, RouteCaseDefinition>
     */
    public function offsetGet(mixed $offset): array
    {
        return $this->get((string) $offset);
    }

    /**
     * @param string $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string) $offset);
    }
}
