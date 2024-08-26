<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Parsers;

use Carte\Parsers\Yaml;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Carte\is_assoc;

class YamlTest extends TestCase
{
    protected string $path = __DIR__ . '/../../resources/formats/format.yml';

    #[Test]
    public function canParseYamlFiles(): void
    {
        $parser = new Yaml();
        $array = $parser->parse($this->path);

        $this->assertTrue(is_assoc($array));
        $this->assertArrayHasKey('format/yml', $array);
        $this->assertEquals(201, $array['format/yml']['code'] ?? null);
        $this->assertEquals('body', $array['format/yml']['body'] ?? null);
    }
}
