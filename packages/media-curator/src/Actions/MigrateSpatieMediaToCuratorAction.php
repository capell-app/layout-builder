<?php

declare(strict_types=1);

namespace Capell\MediaCurator\Actions;

use Capell\MediaCurator\Data\MigrateSpatieMediaInput;
use Capell\MediaCurator\Data\MigrateSpatieMediaResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

/**
 * Migrates existing Spatie MediaLibrary rows into the Curator single-FK model.
 *
 * For each Spatie `media` row it:
 *   1. Derives the target FK column name from the collection name.
 *   2. Resolves the owner's table name via the model class.
 *   3. Skips rows whose target column does not exist on the owner table.
 *   4. Idempotently creates (or reuses) a `curator` row.
 *   5. Updates the owner FK only when currently null.
 *
 * Pass `dryRun=true` to perform all checks and counts without writing.
 */
final class MigrateSpatieMediaToCuratorAction
{
    use AsAction;

    public function handle(MigrateSpatieMediaInput $input): MigrateSpatieMediaResult
    {
        $processed = 0;
        $created = 0;
        $skipped = 0;
        $ownersUpdated = 0;
        $warnings = [];

        $query = DB::table('media');

        if ($input->collections !== []) {
            $query->whereIn('collection_name', $input->collections);
        }

        if ($input->ownerType !== null) {
            $query->where('model_type', $input->ownerType);
        }

        $query->orderBy('id')->chunkById($input->chunkSize, function (iterable $rows) use (
            $input,
            &$processed,
            &$created,
            &$skipped,
            &$ownersUpdated,
            &$warnings,
        ): void {
            foreach ($rows as $spatieRow) {
                $processed++;

                try {
                    $this->processRow(
                        $spatieRow,
                        $input,
                        $created,
                        $skipped,
                        $ownersUpdated,
                        $warnings,
                    );
                } catch (Throwable $throwable) {
                    $warnings[] = sprintf(
                        'Row id=%d: unexpected error — %s',
                        $spatieRow->id,
                        $throwable->getMessage(),
                    );
                }
            }
        });

        return new MigrateSpatieMediaResult(
            processed: $processed,
            created: $created,
            skipped: $skipped,
            ownersUpdated: $ownersUpdated,
            warnings: $warnings,
        );
    }

    /**
     * @param  array<int, string>  $warnings
     */
    private function processRow(
        object $spatieRow,
        MigrateSpatieMediaInput $input,
        int &$created,
        int &$skipped,
        int &$ownersUpdated,
        array &$warnings,
    ): void {
        $column = Str::snake($spatieRow->collection_name) . '_id';

        // Resolve owner table; skip with warning if class is missing.
        $ownerTable = $this->resolveOwnerTable($spatieRow->model_type, $spatieRow->id, $warnings);
        if ($ownerTable === null) {
            return;
        }

        // Skip if the FK column does not exist on the owner table.
        if (! Schema::hasColumn($ownerTable, $column)) {
            $warnings[] = sprintf(
                'Row id=%d: column "%s" does not exist on table "%s" (collection "%s") — skipped.',
                $spatieRow->id,
                $column,
                $ownerTable,
                $spatieRow->collection_name,
            );

            return;
        }

        // Idempotency: find existing curator row by disk + path.
        $existingCuratorRow = DB::table('curator')
            ->where('disk', $spatieRow->disk)
            ->where('path', $spatieRow->file_name)
            ->first();

        if ($existingCuratorRow !== null) {
            $curatorId = $existingCuratorRow->id;
            $skipped++;
        } else {
            // Dry-run short-circuits before any write.
            if ($input->dryRun) {
                return;
            }

            $extension = pathinfo($spatieRow->file_name, PATHINFO_EXTENSION);
            $directory = dirname($spatieRow->file_name);
            $directory = ($directory === '.' || $directory === '') ? '' : $directory;

            $curatorId = DB::transaction(fn (): int => (int) DB::table('curator')->insertGetId([
                'disk' => $spatieRow->disk,
                'directory' => $directory,
                'visibility' => 'public',
                'name' => $spatieRow->name,
                'path' => $spatieRow->file_name,
                'size' => $spatieRow->size,
                'type' => $spatieRow->mime_type ?? '',
                'ext' => $extension,
                'alt' => null,
                'title' => null,
                'description' => null,
                'caption' => null,
                'exif' => null,
                'curations' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            $created++;
        }

        if ($input->dryRun) {
            return;
        }

        // Only update the FK when it is currently null.
        $updatedRows = DB::table($ownerTable)
            ->where('id', $spatieRow->model_id)
            ->whereNull($column)
            ->update([$column => $curatorId]);

        if ($updatedRows > 0) {
            $ownersUpdated++;
        }
    }

    /**
     * @param  array<int, string>  $warnings
     */
    private function resolveOwnerTable(string $modelType, int $rowId, array &$warnings): ?string
    {
        try {
            /** @var Model $owner */
            $owner = new $modelType;

            return $owner->getTable();
        } catch (Throwable $throwable) {
            $warnings[] = sprintf(
                'Row id=%d: model class "%s" could not be instantiated — %s',
                $rowId,
                $modelType,
                $throwable->getMessage(),
            );

            return null;
        }
    }
}
