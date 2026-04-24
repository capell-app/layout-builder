<?php

declare(strict_types=1);

namespace Capell\MediaCurator\Concerns;

use Capell\Core\Contracts\Media\MediaContract;
use Capell\MediaCurator\Models\CuratorMedia;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Capell media trait for Curator-backed owners. Implements HasMediaContract
 * using a single foreign key column per collection on the owner table.
 *
 * Convention: collection "image" maps to column "image_id"; "socialImage"
 * maps to "social_image_id"; etc. (Str::snake()). Consumer migrations must
 * add the FK columns.
 *
 * Single-FK means ONE media row per collection — no galleries. If you need
 * multi-item collections, stay on the default Spatie backend.
 *
 * @mixin Model
 */
trait InteractsWithCuratorMedia
{
    public static function curatorMediaColumn(string $collection): string
    {
        return Str::snake($collection) . '_id';
    }

    public function curatorMediaRelation(string $collection): BelongsTo
    {
        /** @var Model $this */
        return $this->belongsTo(CuratorMedia::class, static::curatorMediaColumn($collection));
    }

    /**
     * @return Collection<int, MediaContract>
     */
    public function getMedia(string $collection = 'default'): Collection
    {
        $media = $this->getFirstMedia($collection);

        /** @var Collection<int, MediaContract> $collectionResult */
        $collectionResult = $media === null
            ? new Collection
            : new Collection([$media]);

        return $collectionResult;
    }

    public function getFirstMedia(string $collection = 'default'): ?MediaContract
    {
        $column = static::curatorMediaColumn($collection);

        /** @var Model $this */
        $mediaId = $this->{$column} ?? null;

        if ($mediaId === null) {
            return null;
        }

        /** @var CuratorMedia|null $media */
        $media = CuratorMedia::query()->find($mediaId);

        return $media;
    }

    public function getFirstMediaUrl(string $collection = 'default', string $conversion = ''): string
    {
        $media = $this->getFirstMedia($collection);

        return $media?->getUrl($conversion) ?? '';
    }

    public function addMediaFromUploadedFile(UploadedFile $file, string $collection = 'default'): MediaContract
    {
        $storedPath = $file->store('media', 'public');

        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);

        /** @var CuratorMedia $media */
        $media = CuratorMedia::query()->create([
            'disk' => 'public',
            'directory' => 'media',
            'visibility' => 'public',
            'name' => $baseName,
            'path' => $storedPath,
            'size' => $file->getSize(),
            'type' => $file->getMimeType(),
            'ext' => $extension,
            'alt' => null,
            'title' => null,
            'description' => null,
            'caption' => null,
            'exif' => null,
            'curations' => null,
        ]);

        $column = static::curatorMediaColumn($collection);
        /** @var Model $this */
        $this->{$column} = $media->getKey();
        $this->save();

        return $media;
    }

    public function clearMediaCollection(string $collection = 'default'): static
    {
        $column = static::curatorMediaColumn($collection);
        /** @var Model $this */
        $this->{$column} = null;
        $this->save();

        return $this;
    }
}
