<?php

/**
 * Adapted from Alexanderpas\Common\HTTP\Method of the alexanderpas/http-enum package.
 * The original copyright statement is as follows:
 *
 * Copyright Alexander Pas 2021.
 * Distributed under the Boost Software License, Version 1.0.
 * (See accompanying file LICENSE_1_0.txt or copy at https://www.boost.org/LICENSE_1_0.txt)
 */

declare(strict_types=1);

namespace Carte\Http;

use ArchTech\Enums\Values;
use ValueError;

/**
 * String values for HTTP Methods as defined in IETF RFC 5789 and RFC 7231
 * (excluding CONNECT, and including ANY)
 */
enum Method: string
{
    use Values;

    case GET = 'GET';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case TRACE = 'TRACE';
    case DELETE = 'DELETE';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case POST = 'POST';
    case ANY = '*';

    public static function fromName(string $name): Method
    {
        $method = self::tryFromName($name);

        if ($method === null) {
            $enumName = static::class; // phpcs:ignore

            throw new ValueError("$name is not a valid name for enum \"$enumName\"");
        }

        return $method;
    }

    public static function tryFromName(?string $name): ?Method
    {
        if ($name === null || $name === '*') {
            return self::ANY;
        }

        $name = strtoupper($name);

        if (defined("self::$name")) {
            /** @var Method $enumCase */
            $enumCase = constant("self::$name");

            return $enumCase;
        }

        return null;
    }
}
