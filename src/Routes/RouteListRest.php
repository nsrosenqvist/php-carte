<?php

declare(strict_types=1);

namespace Carte\Routes;

use Carte\Abilities\UnitArray;
use Carte\Http\Method;
use Carte\Routes\RouteCase;
use Carte\Routes\RouteList;
use TypeError;
use ValueError;

/**
 * @phpstan-import-type RouteCaseShortValue from RouteCaseShort
 * @phpstan-import-type RouteCaseDefinition from RouteCase
 * @phpstan-type RouteListRestDefinition array<value-of<Method>, int|string|RouteCaseDefinition>
 */
readonly class RouteListRest extends RouteList
{
    /**
     * @param array<array-key, mixed> $definitions
     */
    public static function hasRestSyntax(array $definitions): bool
    {
        return ! empty(array_intersect(array_map('strtoupper', array_keys($definitions)), Method::values()));
    }

    /**
     * @param string                  $pattern    Route pattern
     * @param RouteListRestDefinition $definition Route list REST definition
     *
     * @throws TypeError
     * @throws ValueError
     */
    public static function fromArray(string $pattern, array $definition): static
    {
        $routes = [];

        foreach ($definition as $method => $content) {
            if (is_array($content)) {
                $content['match'] ??= [];
                $content['match']['method'] = $method;
                $routes[] = RouteCase::fromArray($pattern, $content);
            } else {
                $routes[] = new RouteCaseShort($pattern, $content, Method::fromName($method));
            }
        }

        return new static(...$routes);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $keys = array_map(static fn (RouteCase $case) => $case->match?->method->value ?? '*', $this->cases);
        $values = array_map(static function (RouteCase $case) {
            if ($case instanceof UnitArray) {
                return current($case->toArray());
            }

            $definition = $case->toArray();

            if (isset($definition['match']) && isset($definition['match']['method'])) {
                unset($definition['match']['method']);
            }

            return array_filter($definition);
        }, $this->cases);

        return array_combine($keys, $values);
    }
}
