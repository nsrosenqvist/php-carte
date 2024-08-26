<?php

declare(strict_types=1);

namespace Carte\Routes;

use Carte\Abilities\Arrayable;
use Carte\Routes\RouteCaseShort;
use Carte\Routes\RouteGroup;
use Carte\Routes\RouteList;
use Carte\Routes\RouteListRest;
use TypeError;
use ValueError;

use function Carte\is_assoc;

/**
 * @phpstan-import-type RouteGroupDefinition from RouteGroup
 * @phpstan-import-type RouteListDefinition from RouteList
 * @phpstan-import-type RouteListRestDefinition from RouteListRest
 * @phpstan-import-type RouteCaseDefinition from RouteCase
 * @phpstan-type RouteMapDefinition array<string, RouteGroupDefinition|RouteListDefinition>
 * @phpstan-type RouteMapHierarchy array<string, RouteGroup|RouteList>
 * @phpstan-type RouteMapCollapsed array<string, RouteList>
 * @phpstan-type ChildDefinition RouteGroupDefinition|RouteListDefinition|RouteListDefinition|RouteGroup|RouteList
 * @implements Arrayable<string, RouteGroupDefinition|RouteListDefinition>
 */
readonly class RouteMap implements Arrayable
{
    /**
     * @var RouteMapHierarchy
     */
    protected array $map;

    /**
     * @var RouteMapCollapsed
     */
    protected array $collapsed;

    /**
     * @param  RouteMapDefinition $definition Route map definition
     *
     * @throws TypeError
     */
    public function __construct(array $definition = [])
    {
        if (! is_assoc($definition)) {
            throw new TypeError(__METHOD__ . ' definition must be an associative array');
        }

        // Parse child entries and save as map
        $map = array_combine(
            array_keys($definition),
            array_map([$this, 'getChildInstance'], array_keys($definition), $definition),
        );
        $flat = $this->collapseMap($map);

        // Sort according to route specificity
        $this->collapsed = $this->sortMap($flat);
        $this->map = $this->sortMap($map);
    }

    /**
     * @param RouteMapHierarchy $map
     *
     * @return RouteMapCollapsed
     */
    protected function collapseMap(array $map): array
    {
        // Make sure all expanded group route patterns are unique
        /** @var RouteGroup[] $groups */
        $groups = array_filter($map, static fn ($entry) => $entry instanceof RouteGroup);
        /** @var RouteList[] $lists */
        $lists = array_filter($map, static fn ($entry) => $entry instanceof RouteList);
        $flat = [];

        foreach ($groups as $group) {
            // Flatten and make sure we have no conflicts
            $flattened = $group->flatten();

            if (array_intersect_key($flattened, $lists)) {
                throw new ValueError(RouteGroup::class . ' can not contain patterns overwriting existing routes in ' . static::class);
            }

            // Merge routes into map
            $flat = array_merge($flat, $flattened);
        }

        // Merge flattened groups with regular routes
        $flat = array_merge(
            $flat,
            $lists,
        );

        // Normalize keys with "index" suffix
        return array_combine(
            array_map(static function ($pattern) {
                return str_ends_with($pattern, '/')
                    ? ltrim("{$pattern}/index", '/')
                    : ltrim($pattern, '/');
            }, array_keys($flat)),
            $flat,
        );
    }

    /**
     * @param string          $pattern    Route pattern
     * @param ChildDefinition $definition Route definition
     */
    protected function getChildInstance(
        string $pattern,
        mixed $definition,
    ): RouteGroup|RouteList|RouteListRest {
        if ($definition instanceof RouteGroup || $definition instanceof RouteList) {
            return $definition;
        }

        if (! is_array($definition)) {
            return new RouteList(new RouteCaseShort($pattern, $definition));
        }

        if (is_assoc($definition)) {
            if (isset($definition['routes'])) {
                /** @var RouteGroupDefinition $definition */
                return RouteGroup::fromArray($pattern, $definition);
            } elseif (RouteListRest::hasRestSyntax($definition)) {
                /** @var RouteListRestDefinition $definition */
                return RouteListRest::fromArray($pattern, $definition);
            }

            /** @var RouteListDefinition $definition */
            return RouteList::fromArray($pattern, $definition);
        }

        /** @var RouteListDefinition $definition */
        return RouteList::fromArray($pattern, $definition);
    }

    /**
     * @return RouteMapCollapsed
     */
    public function getCollapsed(): array
    {
        return $this->collapsed;
    }

    /**
     * @return RouteMapHierarchy
     */
    public function getMap(): array
    {
        return $this->map;
    }

    /**
     * @param T $map
     *
     * @return T
     *
     * @template T of RouteMapCollapsed|RouteMapHierarchy
     */
    protected function sortMap(array $map): array
    {
        $patterns = array_keys($map);
        $sorted = [];

        foreach ($this->sortPatterns($patterns) as $pattern) {
            $sorted[$pattern] = $map[$pattern];
        }

        return $sorted;
    }

    /**
     * Sort pattern according to route pattern specificity
     *
     * @param string[] $patterns
     *
     * @return string[]
     */
    protected function sortPatterns(array $patterns): array
    {
        // Get all route patterns and split them
        $exploded = array_map(static function ($pattern) {
            return explode('/', $pattern);
        }, $patterns);

        // Sort according to depth and each level by name,
        // the more specific patterns are first
        usort($exploded, static function ($a, $b) {
            $countA = count($a);
            $countB = count($b);

            if (($countA = count($a)) === ($countB = count($b))) {
                $nameA = $a[$countA - 1];
                $nameB = $b[$countB - 1];
                $idPosA = ($pos = strpos($nameA, '{')) !== false ? $pos : -1;
                $idPosB = ($pos = strpos($nameB, '{')) !== false ? $pos : -1;
                $wildPosA = ($pos = strpos($nameA, '*')) !== false ? $pos : -1;
                $wildPosB = ($pos = strpos($nameB, '*')) !== false ? $pos : -1;

                if ($idPosA >= 0 && $idPosB >= 0) {
                    return 0;
                } elseif ($wildPosA !== $wildPosB) {
                    return $wildPosA < $wildPosB ? -1 : 1;
                } elseif ($idPosA !== $idPosB) {
                    return $idPosA < $idPosB ? -1 : 1;
                }

                $joinedA = implode('/', $a);
                $joinedB = implode('/', $b);
                $varCountA = substr_count($joinedA, '{') + substr_count($joinedA, '*');
                $varCountB = substr_count($joinedB, '{') + substr_count($joinedA, '*');

                if ($varCountA !== $varCountB) {
                    return $varCountA < $varCountB ? -1 : 1;
                }

                return strnatcasecmp($joinedA, $joinedB) * -1;
            }

            return $countA < $countB ? 1 : -1;
        });

        // Reassemble the patterns
        return array_map(static function ($part) {
            return implode('/', $part);
        }, $exploded);
    }

    /**
     * @param RouteMapDefinition $definition
     */
    public static function fromArray(array $definition): static
    {
        return new static($definition);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_map(static fn ($entry) => $entry->toArray(), $this->map);
    }

    /**
     * Dump returns the map in a format
     * that is optimized for route matching
     *
     * @return array<string, array<int, RouteCaseDefinition>>
     */
    public function dump(): array
    {
        /** @var array<string, array<int, RouteCaseDefinition>> */
        return array_map(static fn ($entry) => $entry->toArray(), $this->collapsed);
    }
}
