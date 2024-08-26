<?php

declare(strict_types=1);

namespace Carte\Routes;

use Carte\Abilities\Arrayable;
use Carte\Routes\RouteCase;

use function Carte\is_assoc;

/**
 * @phpstan-import-type RouteCaseDefinition from RouteCase
 * @phpstan-type RouteListDefinition RouteCaseDefinition|RouteCaseDefinition[]
 * @implements Arrayable<array-key, mixed>
 */
readonly class RouteList implements Arrayable
{
    /**
     * @var RouteCase[]
     */
    protected array $cases;

    public function __construct(RouteCase ...$cases)
    {
        $this->cases = $this->sortRouteCases($cases);
    }

    /**
     * Get all route cases
     *
     * @return RouteCase[]
     */
    public function getRouteCases(): array
    {
        return $this->cases;
    }

    /**
     * Sort route cases according to match statement specificity
     *
     * @param RouteCase[] $cases
     *
     * @return RouteCase[]
     */
    protected function sortRouteCases(array $cases): array
    {
        if (count($cases) < 1) {
            return $cases;
        }

        usort($cases, static function ($a, $b) {
            $matchA = ! empty($a->match);
            $matchB = ! empty($b->match);
            $methodA = ($matchA && ! empty($a->match->method));
            $methodB = ($matchB && ! empty($b->match->method));
            $queryA = ($matchA && ! empty($a->match->query));
            $queryB = ($matchB && ! empty($b->match->query));

            if ($matchA !== $matchB) {
                return $matchA ? -1 : 1;
            } elseif ($methodA !== $methodB) {
                return $methodA ? -1 : 1;
            } elseif ($queryA !== $queryB) {
                return $queryA ? -1 : 1;
            } elseif ($queryA && $queryB) {
                $countA = count($a->match->query);
                $countB = count($b->match->query);

                if ($countA !== $countB) {
                    return $countA < $countB ? 1 : -1;
                }

                return 0;
            }

            return 0;
        });

        return $cases;
    }

    /**
     * @param string              $pattern    Route pattern
     * @param RouteListDefinition $definition Route list definition
     */
    public static function fromArray(string $pattern, array $definition): static
    {
        if (is_assoc($definition)) {
            /** @var RouteCaseDefinition[] $definition */
            $definition = [$definition];
        }

        $routes = array_map(static fn ($entry) => RouteCase::fromArray($pattern, $entry), $definition);

        return new static(...$routes);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        if (count($this->cases) === 1) {
            return $this->cases[0]->toArray();
        }

        return array_map(static fn ($entry) => $entry->toArray(), $this->cases);
    }
}
