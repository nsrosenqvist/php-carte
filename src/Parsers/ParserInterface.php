<?php

declare(strict_types=1);

namespace Carte\Parsers;

use Carte\Routes\RouteMap;

/**
 * @phpstan-import-type RouteMapDefinition from RouteMap
 */
interface ParserInterface
{
    /**
     * Parse the specified file
     *
     * @return RouteMapDefinition
     */
    public function parse(string $path): array;
}
