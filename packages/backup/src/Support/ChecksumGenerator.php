<?php

declare(strict_types=1);

namespace Capell\Backup\Support;

use RuntimeException;

final class ChecksumGenerator
{
    public static function forString(string $contents): string
    {
        return 'sha256-' . hash('sha256', $contents);
    }

    public static function forFile(string $path): string
    {
        $hash = hash_file('sha256', $path);

        if ($hash === false) {
            throw new RuntimeException(sprintf('Unable to hash file [%s].', $path));
        }

        return 'sha256-' . $hash;
    }
}
