<?php

declare(strict_types=1);

namespace Carte\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Carte\array_map_recursive;
use function Carte\is_assoc;
use function Carte\is_http_redirect;
use function Carte\str_first_line;
use function Carte\strpos_newline;
use function Carte\to_object;

class HelpersTest extends TestCase
{
    #[Test]
    public function isAssocCanCheckForAssociativeArrays(): void
    {
        $array = [1, 2, 3];
        $this->assertNotTrue(is_assoc($array));

        $array = ['one' => 1, 'two' => 2, 'three' => 3];
        $this->assertTrue(is_assoc($array));
    }

    #[Test]
    public function arrayMapRecursiveCanIterateAndMapMultidimensionalArrays(): void
    {
        $mapped = array_map_recursive(static function ($item) {
            return is_string($item) ? 'lorem' : $item;
        }, $array = [
            'first' => [
                'assoc' => [
                    'foo' => 'bar',
                ],
                'regular' => [1, 2, 3],
            ],
        ]);
        $array['first']['assoc']['foo'] = 'lorem';

        $this->assertEquals($array, $mapped);
    }

    #[Test]
    public function toObjectCanConvertAMultidimensionalArrayToAStdClass(): void
    {
        $array = [
            'first' => [
                'assoc' => [
                    'foo' => 'bar',
                ],
                'regular' => [1, 2, 3],
            ],
        ];
        $object = (object) [
            'first' => (object) [
                'assoc' => (object) [
                    'foo' => 'bar',
                ],
                'regular' => [1, 2, 3],
            ],
        ];

        $this->assertEquals($object, to_object($array));
    }

    #[Test]
    public function strFirstLineCanReturnTheFirstLineOfAString(): void
    {
        $url = "HTTP/1.1 200 OK\nContent-Type: application/json\n\n{\"foo\":\"bar\"}";
        $this->assertEquals('HTTP/1.1 200 OK', str_first_line($url));

        $url = "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n{\"foo\":\"bar\"}";
        $this->assertEquals('HTTP/1.1 200 OK', str_first_line($url));

        $url = 'HTTP/1.1 200 OK';
        $this->assertEquals('HTTP/1.1 200 OK', str_first_line($url));
    }

    #[Test]
    public function strPosNewlineCanFindThePositionOfTheFirstNewline(): void
    {
        $url = "HTTP/1.1 200 OK\nContent-Type: application/json\n\n{\"foo\":\"bar\"}";
        $this->assertEquals(15, strpos_newline($url));

        $url = "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n{\"foo\":\"bar\"}";
        $this->assertEquals(15, strpos_newline($url));

        $url = 'HTTP/1.1 200 OK';
        $this->assertNull(strpos_newline($url));
    }

    #[Test]
    public function isHttpRedirectCanCheckThatHttpCodeIsARedirect(): void
    {
        $this->assertTrue(is_http_redirect(301));
        $this->assertTrue(is_http_redirect(302));
        $this->assertTrue(is_http_redirect(303));
        $this->assertTrue(is_http_redirect(307));
        $this->assertTrue(is_http_redirect(308));
        $this->assertNotTrue(is_http_redirect(200));
    }
}
