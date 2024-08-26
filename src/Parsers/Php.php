<?php

declare(strict_types=1);

namespace Carte\Parsers;

use Carte\Abilities\Arrayable;
use Carte\Exceptions\FileNotFoundException;
use Carte\Exceptions\ManifestParseException;
use Carte\Parsers\ParserInterface;
use Throwable;

use function Carte\array_map_recursive;
use function Carte\is_assoc;

class Php implements ParserInterface
{
    /**
     * Parse the specified file
     *
     * @return array<string, mixed>
     *
     * @throws ManifestParseException
     * @throws FileNotFoundException
     */
    public function parse(string $path): array
    {
        if (! file_exists($path)) {
            throw new FileNotFoundException("File not found: $path");
        }

        try {
            $data = require $path;
        } catch (Throwable $e) {
            throw new ManifestParseException('Failed to parse PHP file: ' . $e->getMessage());
        }

        if (! is_array($data) || ! is_assoc($data)) {
            throw new ManifestParseException('Failed to parse PHP file: Invalid data');
        }

        // Convert arrayable objects
        /** @var array<string, mixed> $data */
        $data = array_map_recursive(static function ($item) {
            if ($item instanceof Arrayable) {
                return $item->toArray();
            }

            return $item;
        }, $data);

        return $data;
    }
}
