<?php

declare(strict_types=1);

namespace Carte\Tests\Unit\Routes;

use Carte\Manifest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ManifestTest extends TestCase
{
    protected string $root = __DIR__ . '/../resources/manifests';

    #[Test]
    public function canCacheManifest(): void
    {
        if (file_exists($cachePath = $this->root . '/site.cached')) {
            unlink($cachePath);
        }

        new Manifest($this->root . '/site.yml', $cachePath);

        $this->assertFileExists($cachePath);
        $cache = require $cachePath;
        $this->assertIsArray($cache);
        $this->assertNotEmpty($cache);
    }

    #[Test]
    public function canLoadManifestFromCache(): void
    {
        $manifest = new Manifest($this->root . '/site.yml', $this->root . '/site.precached');

        $this->assertArrayHasKey('foo', $manifest);
        $this->assertEquals('bar', current($manifest['foo']));
    }

    #[Test]
    public function canAccessRoutesThroughArrayAccess(): void
    {
        $manifest = new Manifest($this->root . '/site.yml');

        $this->assertArrayHasKey('blog/home', $manifest);
        $this->assertEquals('blog:home', current($manifest['blog/home']));
    }

    #[Test]
    public function canAccessRoutesThroughPropertyAccess(): void
    {
        $manifest = new Manifest($this->root . '/site.yml');

        $this->assertTrue(isset($manifest->{'blog/home'}));
        $this->assertEquals('blog:home', current($manifest->{'blog/home'}));
    }
}
