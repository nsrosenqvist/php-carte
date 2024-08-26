<?php

declare(strict_types=1);

namespace Carte\Routes;

use Carte\Abilities\UnitArray;
use Carte\Http\Method;
use Carte\Routes\RouteCase;
use Carte\Routes\RouteMatch;
use TypeError;

/**
 * @phpstan-type RouteCaseShortValue int|string
 * @phpstan-type RouteCaseShortDefinition array{0: RouteCaseShortValue}
 */
readonly class RouteCaseShort extends RouteCase implements UnitArray
{
    /**
     * @throws TypeError
     */
    public function __construct(
        string $pattern,
        int|string $content,
        ?Method $method = null,
    ) {
        $match = new RouteMatch($pattern, $method);
        $code = (filter_var($content, FILTER_VALIDATE_INT)) ? (int) $content : null;
        $body = (! $code && $content) ? (string) $content : null;

        if (! $code && ! $body) {
            throw new TypeError('Short route case content must be an integer or a string');
        }

        parent::__construct(
            pattern: $pattern,
            match: $match,
            code: $code,
            body: $body,
        );
    }

    /**
     * @param string                   $pattern    Route pattern
     * @param RouteCaseShortDefinition $definition Short route case definition
     */
    public static function fromArray(string $pattern, array $definition): static
    {
        return new static($pattern, content: current($definition));
    }

    /**
     * @return RouteCaseShortDefinition
     */
    public function toArray(): array
    {
        return [$this->code ?? $this->body]; // @phpstan-ignore-line
    }
}
