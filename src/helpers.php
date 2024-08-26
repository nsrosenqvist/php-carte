<?php

declare(strict_types=1);

namespace Carte;

use stdClass;

/**
 * Perform a recursive array map
 *
 * @see https://stackoverflow.com/a/39637749
 *
 * @param callable                $callback The callback function to use
 * @param array<array-key, mixed> $array    The array to map
 *
 * @return array<array-key, mixed>
 */
function array_map_recursive(callable $callback, array $array): array
{
    $func = static function ($item) use (&$func, &$callback) {
        return is_array($item) ? array_map($func, $item) : call_user_func($callback, $item);
    };

    return array_map($func, $array);
}

/**
 * Cast a multi-dimensional array into an object
 *
 * @see https://gist.github.com/machitgarha/e47ce6580cd0964e8a71cf8eb1e52644
 *
 * @param array<array-key, mixed> $array The array to cast
 */
function to_object(array $array): object
{
    $object = new stdClass();

    foreach ($array as $key => $value) {
        if (! is_string($key) || ! strlen($key)) {
            continue;
        }

        if (is_array($value) && is_assoc($value)) {
            $object->{$key} = to_object($value);
        } else {
            $object->{$key} = $value;
        }
    }

    return $object;
}

/**
 * Determine if the array is associative or not
 *
 * @see https://www.php.net/manual/en/function.is-array.php#84488
 *
 * @param array<array-key, mixed> $array The array to check
 */
function is_assoc(array $array): bool
{
    foreach (array_keys($array) as $key => $value) {
        if ($key !== $value) {
            return true;
        }
    }

    return false;
}

/**
 * Return the first line of a string
 */
function str_first_line(string $url): string
{
    return substr($url, 0, strpos_newline($url));
}

/**
 * Find the position of the first new line character in a string or null if not found
 */
function strpos_newline(string $url): ?int
{
    $url = ltrim($url);
    $br = strpos($url, "\r\n");

    if ($br === false) {
        $br = strpos($url, "\n");
    }

    return $br ?: null;
}

/**
 * Check whether the HTTP code is a redirect
 */
function is_http_redirect(int $code): bool
{
    return in_array($code, [301, 302, 303, 307, 308]);
}
