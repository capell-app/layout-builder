<?php

declare(strict_types=1);

$viewsDirectory = dirname(__DIR__) . '/storage/framework/views';

if (! is_dir($viewsDirectory)) {
    return;
}

$viewDirectories = glob($viewsDirectory . '/phpunit-*', GLOB_ONLYDIR) ?: [];

foreach ($viewDirectories as $viewDirectory) {
    removeDirectory($viewDirectory);
}

function removeDirectory(string $directory): void
{
    for ($attempt = 0; $attempt < 3; $attempt++) {
        if (! is_dir($directory)) {
            return;
        }

        removeDirectoryContents($directory);

        if (! is_dir($directory) || @rmdir($directory)) {
            return;
        }

        usleep(100000);
    }
}

function removeDirectoryContents(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    $entries = @scandir($directory);

    if ($entries === false) {
        return;
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $path = $directory . DIRECTORY_SEPARATOR . $entry;

        if (is_dir($path) && ! is_link($path)) {
            removeDirectory($path);

            continue;
        }

        if (file_exists($path) || is_link($path)) {
            @unlink($path);
        }
    }
}
