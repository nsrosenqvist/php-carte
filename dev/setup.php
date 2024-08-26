#!/usr/bin/env php
<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = dirname(__DIR__);

// Add git hooks
foreach (glob(__DIR__ . '/hooks/*') as $path) {
    $name = basename($path);
    $target = realpath("$root/dev/hooks/$name");
    $link = "$root/.git/hooks/$name";

    if (! is_link($link) && ! @file_exists($link)) {
        echo "Updating hooks: linking $name" . PHP_EOL;
        symlink($target, $link);
        chmod($target, 0770);
    }
}
