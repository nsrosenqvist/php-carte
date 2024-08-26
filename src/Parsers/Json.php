<?php

declare(strict_types=1);

namespace Carte\Parsers;

use Carte\Exceptions\FileNotFoundException;
use Carte\Exceptions\ManifestParseException;
use Carte\Parsers\ParserInterface;

use function Carte\is_assoc;

class Json implements ParserInterface
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

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new ManifestParseException('Failed to read file');
        }

        $data = json_decode($contents, true);

        if (! is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            throw new ManifestParseException('Failed to parse Json file: ' . json_last_error_msg());
        }

        if (! is_array($data) || ! is_assoc($data)) {
            throw new ManifestParseException('Failed to parse Yaml file: Invalid data');
        }

        return $data;
    }
}
