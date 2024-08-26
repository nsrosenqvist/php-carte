<?php

declare(strict_types=1);

namespace Carte\Routes;

use Carte\Abilities\Arrayable;
use Carte\Http\Method;
use CastToType;
use Spatie\Cloneable\Cloneable;
use ValueError;

/**
 * @phpstan-type RouteMatchDefinition array{
 *     method?: value-of<Method>,
 *     query?: array<string, mixed>,
 *     ...
 * }
 * @implements Arrayable<string, mixed>
 */
readonly class RouteMatch implements Arrayable
{
    use Cloneable;

    /**
     * Route pattern
     */
    public string $pattern;

    /**
     * HTTP method
     */
    public ?Method $method;

    /**
     * Query conditions
     *
     * @var array<string, mixed>
     */
    public array $query;

    /**
     * Pattern variables
     *
     * @var array<string, mixed>
     */
    public array $variables;

    /**
     * @param string               $pattern   Route pattern
     * @param Method               $method    HTTP method
     * @param array<string, mixed> $query     Query conditions
     * @param array<string, mixed> $variables Pattern variables
     */
    public function __construct(
        string $pattern,
        ?Method $method = null,
        array $query = [],
        array $variables = [],
    ) {
        $this->pattern = trim($pattern, '/');
        $this->method = $method;
        $this->query = $query;
        $this->variables = $variables;
    }

    /**
     * @param string               $endpoint Request target
     * @param Method               $method   HTTP method
     * @param array<string, mixed> $params   Query parameters
     */
    public function evaluate(string $endpoint, Method $method, array $params): bool
    {
        $endpoint = trim($endpoint, '/');

        // Make sure method matches
        if (! empty($this->method) && $method->value !== $this->method->value) {
            return false;
        }

        // Make sure that the route pattern matches the current request URI
        $pattern = $this->populatePatternVariables($this->pattern, $this->variables);

        if (! fnmatch($pattern, $endpoint)) {
            return false;
        }

        // Also check query conditions
        if (! empty($this->query) && ! $this->evaluateCondition($this->query, $params)) {
            return false;
        }

        return true;
    }

    /**
     * Populate pattern variables with specified strings
     *
     * @param string               $pattern   Route pattern
     * @param array<string, mixed> $variables Pattern variables
     */
    public function populatePatternVariables(string $pattern, array $variables): string
    {
        if (empty($variables)) {
            return $pattern;
        }

        $values = array_values($variables);
        $needles = array_map(static function ($name) {
            return '{' . $name . '}';
        }, array_keys($variables));

        return str_replace($needles, $values, $pattern);
    }

    /**
     * Evaluate query condition
     *
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $query
     */
    protected function evaluateCondition(array $conditions, array $query): bool
    {
        $success = true;

        foreach ($conditions as $key => $expected) {
            $value = $query[$key] ?? null;
            $success = $this->assertExpectation($expected, $value);

            if (! $success) {
                break;
            }
        }

        return $success;
    }

    protected function assertExpectation(mixed $expected, mixed $actual): bool
    {
        return match ($expected) {
            '__isset__'   => $actual !== null,
            '__missing__' => $actual === null,
            '__true__'    => CastToType::_bool($actual) === true,
            '__false__'   => CastToType::_bool($actual) === false,
            '__bool__'    => CastToType::_bool($actual) !== null,
            '__string__'  => is_string($actual),
            '__numeric__' => is_numeric($actual),
            '__int__'     => is_scalar($actual) && (string) CastToType::_int(round((float) $actual)) === (string) $actual, // @phpstan-ignore-line
            '__float__'   => is_scalar($actual) && (string) CastToType::_float($actual) === (string) $actual && strpos((string) $actual, '.') !== false, // @phpstan-ignore-line
            '__array__'   => is_array($actual),
            default => (static function () use ($actual, $expected) {
                if ($actual === null) {
                    return false;
                }

                // Arrays are treated as an "in" condition,
                // therefore we can test it the same as we
                // we would a regular direct comparison
                $expected = is_array($expected) ? $expected : [$expected];

                foreach ($expected as $x) {
                    if ($x === CastToType::cast($actual, gettype($x))) {
                        return true;
                    }
                }

                return false;
            })()
        };
    }

    /**
     * Any key not defined as either a method or query condition will be treated
     * as a pattern variable
     *
     * @param string               $pattern    Route pattern
     * @param RouteMatchDefinition $definition Route match definition
     *
     * @throws ValueError
     */
    public static function fromArray(string $pattern, array $definition): static
    {
        $method = (isset($definition['method']))
            ? Method::from(strtoupper($definition['method']))
            : null;

        $variables = array_filter(
            $definition,
            static fn (string $key) => ! in_array($key, ['method', 'query']),
            ARRAY_FILTER_USE_KEY,
        );

        return new static(
            pattern: $pattern,
            method: $method,
            query: $definition['query'] ?? [],
            variables: $variables,
        );
    }

    /**
     * @return RouteMatchDefinition
     */
    public function toArray(): array
    {
        return array_filter(array_merge([ // @phpstan-ignore-line
            'method' => $this->method?->value,
            'query' => $this->query,
        ], $this->variables));
    }
}
