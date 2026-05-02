<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Data\Dashboard;

use Spatie\LaravelData\Data;

final class MigrationsHealthData extends Data
{
    /**
     * @param  list<string>  $pendingMigrations
     * @param  list<array{package: string, name: string, expectedPath: string}>  $orphanedRegistrations
     */
    public function __construct(
        public readonly int $pendingCount,
        public readonly int $orphanedCount,
        public readonly array $pendingMigrations,
        public readonly array $orphanedRegistrations,
        public readonly ?int $lastBatch,
    ) {}

    public function isAllGreen(): bool
    {
        return $this->pendingCount === 0 && $this->orphanedCount === 0;
    }
}
