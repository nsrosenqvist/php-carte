<?php

declare(strict_types=1);

namespace Carte\Routes;

use Carte\Abilities\Arrayable;
use Carte\Routes\RouteCase;
use Carte\Routes\RouteList;
use Carte\Strategies\StrategyInterface;
use Spatie\Cloneable\Cloneable;
use TypeError;

/**
 * @phpstan-import-type RouteListDefinition from RouteList
 * @phpstan-import-type RouteListRestDefinition from RouteListRest
 * @phpstan-import-type RouteCaseDefinition from RouteCase
 * @phpstan-import-type RouteCaseShortDefinition from RouteCaseShort
 * @phpstan-type RouteGroupDefinition array{
 *     routes: array<string, mixed>,
 *     strategy?: class-string<StrategyInterface>,
 *     extras?: array<string, mixed>,
 * }
 * @implements Arrayable<string, mixed>
 */
readonly class RouteGroup implements Arrayable
{
    use Cloneable;

    /**
     * Route pattern
     */
    public string $pattern;

    /**
     * Route map
     */
    public RouteMap $routes;

    /**
     * @var class-string<StrategyInterface>|null
     */
    public ?string $strategy;

    /**
     * @var array<string, mixed>
     */
    public array $extras;

    /**
     * @param string                               $pattern  Route pattern
     * @param RouteMap                             $routes   Route map
     * @param class-string<StrategyInterface>|null $strategy Chosen middleware strategy
     * @param array<string, mixed>                 $extras   Extra properties
     */
    public function __construct(
        string $pattern,
        RouteMap $routes,
        ?string $strategy = null,
        array $extras = [],
    ) {
        $this->pattern = $pattern;
        $this->routes = $routes;
        $this->strategy = $strategy;
        $this->extras = $extras;
    }

    /**
     * @return array<string, RouteList>
     */
    public function flatten(): array
    {
        $routes = [];

        // Extract all grouped routes and merge their patterns,
        // and assign the group strategy if it's not already defined
        foreach ($this->routes->getMap() as $pattern => $entry) {
            $pattern = "{$this->pattern}/{$pattern}";

            // Merge routes from nested groups
            if ($entry instanceof RouteGroup) {
                $routes = array_merge(
                    $routes,
                    $entry->with(
                        pattern: $pattern,
                        strategy: $entry->strategy ?? $this->strategy,
                        extras: array_merge($this->extras, $entry->extras),
                    )->flatten(),
                );
                continue;
            }

            // Extract cases into list
            $listDefinition = array_map(function (RouteCase $case) use ($pattern) {
                return $case->with(
                    pattern: $pattern,
                    strategy: $case->strategy ?? $this->strategy,
                    extras: array_merge($this->extras, $case->extras),
                    match: $case->match->with(
                        pattern: $pattern,
                    ),
                );
            }, $entry->getRouteCases());

            $routes[$pattern] = new RouteList(...$listDefinition);
        }

        return $routes;
    }

    /**
     * @param string               $pattern    Route pattern
     * @param RouteGroupDefinition $definition Route group definition
     *
     * @throws TypeError
     */
    public static function fromArray(string $pattern, array $definition): static
    {
        // Validate strategy
        $strategy = $definition['strategy'] ?? null;

        if (is_string($strategy)) {
            if (! class_exists($strategy) || ! in_array(StrategyInterface::class, class_implements($strategy))) {
                throw new TypeError("Invalid strategy: {$strategy}");
            }
        }

        // Allow for custom properties
        $properties = get_class_vars(self::class);
        $extras = array_diff_key($definition, $properties);
        $definition = array_diff_key($definition, $extras);

        /** @var array<string, mixed> $explicit */
        $explicit = $definition['extras'] ?? [];
        $definition['extras'] = array_merge($explicit, $extras);

        /** @var array<string, RouteGroupDefinition|RouteListDefinition> $routes */
        $routes = $definition['routes'] ?? [];

        return new static(
            pattern: $pattern,
            strategy: $strategy,
            routes: new RouteMap($routes),
            extras: $definition['extras'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'strategy' => $this->strategy,
            'routes' => $this->routes->toArray(),
            'extras' => $this->extras,
        ]);
    }
}
