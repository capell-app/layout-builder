<?php

declare(strict_types=1);

namespace Capell\MediaCurator\Console;

use Capell\MediaCurator\Actions\MigrateSpatieMediaToCuratorAction;
use Capell\MediaCurator\Data\MigrateSpatieMediaInput;
use Capell\MediaCurator\Data\MigrateSpatieMediaResult;
use Illuminate\Console\Command;

/**
 * Artisan command that moves existing Spatie MediaLibrary rows into the
 * Curator single-FK model by populating per-collection FK columns on owner tables.
 *
 * Usage:
 *   php artisan capell:media-migrate-to-curator
 *   php artisan capell:media-migrate-to-curator --dry-run
 *   php artisan capell:media-migrate-to-curator --collection=image --collection=hero
 *   php artisan capell:media-migrate-to-curator --owner-type="App\Models\Post"
 *   php artisan capell:media-migrate-to-curator --chunk=500
 */
final class MigrateSpatieToCuratorCommand extends Command
{
    /** @var string */
    protected $signature = 'capell:media-migrate-to-curator
                            {--dry-run : Report what would happen without writing}
                            {--collection=* : Spatie collection names to migrate (repeatable; default: all)}
                            {--owner-type= : Restrict migration to this owner model FQCN}
                            {--chunk=200 : Number of Spatie media rows to process per chunk}';

    /** @var string */
    protected $description = 'Move existing Spatie media rows into the Curator backend by populating per-collection FK columns on owner tables.';

    public function handle(MigrateSpatieMediaToCuratorAction $action): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $chunkSize = (int) ($this->option('chunk') ?? 200);

        $input = new MigrateSpatieMediaInput(
            dryRun: $isDryRun,
            collections: (array) ($this->option('collection') ?? []),
            chunkSize: $chunkSize > 0 ? $chunkSize : 200,
            ownerType: $this->option('owner-type') ?: null,
        );

        if ($isDryRun) {
            $this->info('[Dry run] No data will be written.');
        }

        $result = $action->handle($input);

        $this->printSummary($result, $isDryRun);

        return self::SUCCESS;
    }

    private function printSummary(MigrateSpatieMediaResult $result, bool $isDryRun): void
    {
        $label = $isDryRun ? ' (dry run)' : '';

        $this->newLine();
        $this->line(sprintf('<fg=cyan>Migration summary%s</>', $label));
        $this->table(
            ['Stat', 'Count'],
            [
                ['Processed', $result->processed],
                ['Curator rows created', $result->created],
                ['Curator rows reused (idempotent)', $result->skipped],
                ['Owner FKs populated', $result->ownersUpdated],
                ['Warnings', count($result->warnings)],
            ],
        );

        if ($result->warnings !== []) {
            $this->newLine();
            $this->warn('Warnings:');
            $this->table(
                ['#', 'Message'],
                array_map(
                    static fn (int $index, string $message): array => [$index + 1, $message],
                    array_keys($result->warnings),
                    $result->warnings,
                ),
            );
        }
    }
}
