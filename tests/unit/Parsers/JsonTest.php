<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Parsers;

use Carte\Parsers\Json;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Carte\is_assoc;

class JsonTest extends TestCase
{
    protected string $path = __DIR__ . '/../../resources/formats/format.json';

    #[Test]
    public function canParseJsonFiles(): void
    {
        $parser = new Json();
        $array = $parser->parse($this->path);

        $this->assertTrue(is_assoc($array));
        $this->assertArrayHasKey('format/json', $array);
        $this->assertEquals(201, $array['format/json']['code'] ?? null);
        $this->assertEquals('body', $array['format/json']['body'] ?? null);
    }
}
