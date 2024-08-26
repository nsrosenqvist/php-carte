<?php

declare(strict_types=1);

namespace Carte\Parsers;

use Carte\Exceptions\FileNotFoundException;
use Carte\Exceptions\ManifestParseException;
use Carte\Parsers\ParserInterface;
use Symfony\Component\Yaml\Yaml as YamlParser;
use Throwable;

use function Carte\is_assoc;

class Yaml implements ParserInterface
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
            $data = YamlParser::parseFile($path, YamlParser::PARSE_CONSTANT);
        } catch (Throwable $e) {
            throw new ManifestParseException('Failed to parse Yaml file: ' . $e->getMessage());
        }

        if (! is_array($data) || ! is_assoc($data)) {
            throw new ManifestParseException('Failed to parse Yaml file: Invalid data');
        }

        return $data;
    }
}
