<?php

declare(strict_types=1);

namespace Capell\MediaCurator\Actions;

use Capell\MediaCurator\Data\MigrateSpatieMediaInput;
use Capell\MediaCurator\Data\MigrateSpatieMediaResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;
use Throwable;

/**
 * Migrates existing Spatie MediaLibrary rows into the Curator single-FK model.
 *
 * For each Spatie `media` row it:
 *   1. Derives the target FK column name from the collection name.
 *   2. Resolves the owner's table name via the model class.
 *   3. Skips rows whose target column does not exist on the owner table.
 *   4. Resolves the Spatie disk-relative path using MediaLibrary path generation.
 *   5. Idempotently creates (or reuses) a `curator` row.
 *   6. Updates the owner FK only when currently null.
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

        $spatieMedia = $this->hydrateSpatieMedia($spatieRow);
        $path = ltrim($spatieMedia->getPathRelativeToRoot(), '/');
        $directory = pathinfo($path, PATHINFO_DIRNAME);
        $directory = ($directory === '.' || $directory === '') ? '' : $directory;

        // Idempotency: find existing curator row by disk + path.
        $existingCuratorRow = DB::table('curator')
            ->where('disk', $spatieRow->disk)
            ->where('path', $path)
            ->first();

        if ($existingCuratorRow !== null) {
            $curatorId = $existingCuratorRow->id;
            $skipped++;
        } else {
            $extension = pathinfo($spatieRow->file_name, PATHINFO_EXTENSION);
            $metadata = $this->mapMetadata($spatieMedia);

            $created++;

            if ($input->dryRun) {
                $this->incrementProjectedOwnerUpdate($ownerTable, $spatieRow, $column, $ownersUpdated);

                return;
            }

            $curatorId = DB::transaction(fn (): int => DB::table('curator')->insertGetId([
                'disk' => $spatieRow->disk,
                'directory' => $directory,
                'visibility' => 'public',
                'name' => $spatieRow->name,
                'path' => $path,
                'width' => $metadata['width'],
                'height' => $metadata['height'],
                'size' => $spatieRow->size,
                'type' => $spatieRow->mime_type ?? '',
                'ext' => $extension,
                'alt' => $metadata['alt'],
                'title' => $metadata['title'],
                'description' => $metadata['description'],
                'caption' => $metadata['caption'],
                'exif' => $this->encodeJson($metadata['exif']),
                'curations' => $this->encodeJson($metadata['curations']),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        if ($input->dryRun) {
            $this->incrementProjectedOwnerUpdate($ownerTable, $spatieRow, $column, $ownersUpdated);

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

    private function hydrateSpatieMedia(object $spatieRow): SpatieMedia
    {
        $configuredMediaModelClass = config('media-library.media_model', SpatieMedia::class);
        $mediaModelClass = is_string($configuredMediaModelClass) && is_subclass_of($configuredMediaModelClass, SpatieMedia::class)
            ? $configuredMediaModelClass
            : SpatieMedia::class;

        /** @var SpatieMedia $spatieMedia */
        $spatieMedia = (new $mediaModelClass)->newFromBuilder((array) $spatieRow);

        return $spatieMedia;
    }

    private function incrementProjectedOwnerUpdate(
        string $ownerTable,
        object $spatieRow,
        string $column,
        int &$ownersUpdated,
    ): void {
        $wouldUpdateOwner = DB::table($ownerTable)
            ->where('id', $spatieRow->model_id)
            ->whereNull($column)
            ->exists();

        if ($wouldUpdateOwner) {
            $ownersUpdated++;
        }
    }

    /**
     * @return array{
     *     alt: string|null,
     *     title: string|null,
     *     description: string|null,
     *     caption: string|null,
     *     width: int|null,
     *     height: int|null,
     *     exif: array<string, mixed>|null,
     *     curations: array<int, array<string, mixed>>|null
     * }
     */
    private function mapMetadata(SpatieMedia $spatieMedia): array
    {
        $customProperties = $this->decodeArray($spatieMedia->getAttribute('custom_properties'));
        $manipulations = $this->decodeArray($spatieMedia->getAttribute('manipulations'));
        $generatedConversions = $this->decodeArray($spatieMedia->getAttribute('generated_conversions'));
        $responsiveImages = $this->decodeArray($spatieMedia->getAttribute('responsive_images'));
        $exif = $this->decodeArray(Arr::get($customProperties, 'exif'));

        $unmappedCustomProperties = Arr::except($customProperties, [
            'alt',
            'alt_text',
            'alternative_text',
            'title',
            'description',
            'caption',
            'width',
            'height',
            'dimensions',
            'image',
            'original',
            'exif',
            'curations',
        ]);

        $spatieMetadata = array_filter([
            'uuid' => $spatieMedia->getAttribute('uuid'),
            'collection_name' => $spatieMedia->getAttribute('collection_name'),
            'conversions_disk' => $spatieMedia->getAttribute('conversions_disk'),
            'order_column' => $spatieMedia->getAttribute('order_column'),
            'custom_properties' => $unmappedCustomProperties,
            'manipulations' => $manipulations,
            'generated_conversions' => $generatedConversions,
            'responsive_images' => $responsiveImages,
        ], static fn (mixed $value): bool => ! in_array($value, [null, [], ''], true));

        if ($spatieMetadata !== []) {
            $exif['spatie_media_library'] = $spatieMetadata;
        }

        return [
            'alt' => $this->firstString($customProperties, ['alt', 'alt_text', 'alternative_text']),
            'title' => $this->firstString($customProperties, ['title']),
            'description' => $this->firstString($customProperties, ['description']),
            'caption' => $this->firstString($customProperties, ['caption']),
            'width' => $this->firstInt($customProperties, ['width', 'dimensions.width', 'image.width', 'original.width']),
            'height' => $this->firstInt($customProperties, ['height', 'dimensions.height', 'image.height', 'original.height']),
            'exif' => $exif === [] ? null : $exif,
            'curations' => $this->normalizeCurations($customProperties),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<int, string>  $keys
     */
    private function firstString(array $source, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = Arr::get($source, $key);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<int, string>  $keys
     */
    private function firstInt(array $source, array $keys): ?int
    {
        foreach ($keys as $key) {
            $value = Arr::get($source, $key);

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $customProperties
     * @return array<int, array<string, mixed>>|null
     */
    private function normalizeCurations(array $customProperties): ?array
    {
        $curations = $this->decodeArray(Arr::get($customProperties, 'curations'));

        if ($curations === []) {
            return null;
        }

        $normalizedCurations = [];

        foreach ($curations as $curation) {
            if (! is_array($curation)) {
                return null;
            }

            if (isset($curation['curation']) && is_array($curation['curation'])) {
                $normalizedCurations[] = $curation;

                continue;
            }

            if (isset($curation['key'])) {
                $normalizedCurations[] = ['curation' => $curation];

                continue;
            }

            return null;
        }

        return $normalizedCurations;
    }

    private function encodeJson(?array $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
