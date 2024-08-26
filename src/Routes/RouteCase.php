<?php

declare(strict_types=1);

namespace Carte\Routes;

use Alexanderpas\Common\HTTP\StatusCode;
use Carte\Abilities\Arrayable;
use Carte\Routes\RouteMatch;
use Carte\Strategies\StrategyInterface;
use Spatie\Cloneable\Cloneable;
use TypeError;
use ValueError;

/**
 * @phpstan-import-type RouteMatchDefinition from RouteMatch
 * @phpstan-type RouteCaseDefinition array{
 *     match?: RouteMatchDefinition,
 *     strategy?: class-string<StrategyInterface>,
 *     code?: int,
 *     body?: string,
 *     headers?: array<string, string>,
 *     version?: string|float,
 *     reason?: string,
 *     extras?: array<string, mixed>,
 *     ...
 * }
 * @implements Arrayable<array-key, mixed>
 */
readonly class RouteCase implements Arrayable
{
    use Cloneable;

    /**
     * Route pattern
     */
    public string $pattern;

    /**
     * Route matcher
     */
    public RouteMatch $match;

    /**
     * Chosen middleware strategy
     *
     * @var class-string<StrategyInterface>|null
     */
    public ?string $strategy;

    /**
     * Body content
     */
    public ?string $body;

    /**
     * HTTP status code
     */
    public ?int $code;

    /**
     * HTTP headers
     *
     * @var array<string, string>
     */
    public array $headers;

    /**
     * Protocol version
     */
    public string|float|null $version;

    /**
     * Reason phrase
     */
    public ?string $reason;

    /**
     * Additional route definition properties
     *
     * @var array<string, mixed>
     */
    public array $extras;

    /**
     * @param string                               $pattern  Route pattern
     * @param class-string<StrategyInterface>|null $strategy Middleware strategy
     * @param RouteMatch|null                      $match    Route matcher
     * @param int|null                             $code     HTTP status code
     * @param string|null                          $body     Body content
     * @param array<string, string>                $headers  HTTP headers
     * @param string|float|null                    $version  Protocol version
     * @param string|null                          $reason   Reason phrase
     * @param array<string, mixed>                 $extras   Additional route definition properties
     *
     * @throws ValueError;
     */
    public function __construct(
        string $pattern,
        ?string $strategy = null,
        ?RouteMatch $match = null,
        ?int $code = null,
        ?string $body = null,
        array $headers = [],
        string|float|null $version = null,
        ?string $reason = null,
        array $extras = [],
    ) {
        $this->pattern = $pattern;
        $this->match = $match ?? new RouteMatch($pattern);

        $this->strategy = $strategy;
        $this->body = $body;
        $this->code = $code;
        $this->headers = $headers;
        $this->reason = $reason;
        $this->version = $version;
        $this->extras = $extras;

        if ($this->code && StatusCode::tryFrom($this->code) === null) {
            throw new ValueError("Invalid status code: {$this->code}");
        }

        if ($this->version && ! filter_var($this->version, FILTER_VALIDATE_FLOAT)) {
            throw new ValueError("Invalid protocol version: {$this->version}");
        }
    }

    /**
     * @param string              $pattern    Route pattern
     * @param RouteCaseDefinition $definition Route case definition
     *
     * @throws TypeError
     * @throws ValueError
     */
    public static function fromArray(string $pattern, array $definition): static
    {
        // Create match object
        $definition['match'] = RouteMatch::fromArray($pattern, $definition['match'] ?? []);

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

        return new static($pattern, ...$definition);
    }

    /**
     * @return RouteCaseDefinition
     */
    public function toArray(): array
    {
        return array_filter([
            'match' => $this->match->toArray(),
            'strategy' => $this->strategy,
            'code' => $this->code,
            'body' => $this->body,
            'headers' => $this->headers,
            'version' => $this->version,
            'reason' => $this->reason,
            'extras' => $this->extras,
        ]);
    }
}
