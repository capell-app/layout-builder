<?php

declare(strict_types=1);

namespace Capell\Tests\Fixtures;

use Capell\Core\Support\Migration\MigrationFilesystemInterface;

class FakeMigrationFileManager implements MigrationFilesystemInterface
{
    public array $calls = [];

    public function __construct(private array $overrides = []) {}

    public function fileExists(string $path): bool
    {
        $this->calls[] = ['fileExists', $path];

        return $this->overrides['fileExists'][$path] ?? false;
    }

    public function isDir(string $path): bool
    {
        $this->calls[] = ['isDir', $path];

        return $this->overrides['isDir'][$path] ?? true;
    }

    public function makeDir(string $path): void
    {
        $this->calls[] = ['makeDir', $path];
    }

    public function copy(string $from, string $to): void
    {
        $this->calls[] = ['copy', $from, $to];
    }
}
