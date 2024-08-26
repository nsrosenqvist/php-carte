<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Parsers;

use Carte\Parsers\Jsonc;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Carte\is_assoc;

class JsoncTest extends TestCase
{
    protected string $path = __DIR__ . '/../../resources/formats/format.jsonc';

    #[Test]
    public function canParseJsoncFiles(): void
    {
        $parser = new Jsonc();
        $array = $parser->parse($this->path);

        $this->assertTrue(is_assoc($array));
        $this->assertArrayHasKey('format/jsonc', $array);
        $this->assertEquals(201, $array['format/jsonc']['code'] ?? null);
        $this->assertEquals('body', $array['format/jsonc']['body'] ?? null);
    }
}
