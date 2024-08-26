<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Parsers;

use Carte\Parsers\Php;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Carte\is_assoc;

class PhpTest extends TestCase
{
    protected string $path = __DIR__ . '/../../resources/formats/format.php';

    #[Test]
    public function canParsePhpFiles(): void
    {
        $parser = new Php();
        $array = $parser->parse($this->path);

        $this->assertTrue(is_assoc($array));
        $this->assertArrayHasKey('format/php', $array);
        $this->assertEquals(201, $array['format/php']['code'] ?? null);
        $this->assertEquals('body', $array['format/php']['body'] ?? null);
    }
}
