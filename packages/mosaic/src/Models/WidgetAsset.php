<?php

declare(strict_types=1);

namespace Capell\Mosaic\Models;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\AssetRelation;
use Capell\Core\Models\Concerns\HasAssets;
use Capell\Core\Models\Concerns\HasMetaData;
use Capell\Core\Models\Concerns\HasUserstamps;
use Capell\Core\Models\Contracts\Userstampable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Translation;
use Capell\Media\Models\Concerns\InteractsWithMedia;
use Capell\Mosaic\Database\Factories\WidgetAssetFactory;
use Capell\Mosaic\Models\Concerns\ComposhipsJsonRelationshipsTrait;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Capell\Mosaic\Models\WidgetAsset
 *
 * @property int $id
 * @property string $container
 * @property string|null $pageable_type
 * @property int|null $pageable_id
 * @property array|null $meta
 * @property int|null $occurrence
 * @property int|null $order
 * @property int|null $asset_id
 * @property string|null $asset_type
 * @property int|null $widget_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property-read Model|Model<Pageable>|Section $asset
 * @property-read User|null $creator
 * @property-read User|null $destroyer
 * @property-read User|null $editor
 * @property-read string $asset_key
 * @property-read Media|null $image
 * @property-read Model<Pageable>|null $page
 * @property-read Model<Pageable>|null $relatedPage
 * @property-read Widget|null $widget
 * @property-read Collection|Section[] $related
 * @property-read int|null $related_count
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 *
 * @method static WidgetAssetFactory factory($count = null, $state = [])
 * @method static Builder<static>|WidgetAsset newModelQuery()
 * @method static Builder<static>|WidgetAsset newQuery()
 * @method static Builder<static>|WidgetAsset ordered(string $dir = 'asc')
 * @method static Builder<static>|WidgetAsset alphabetical(Language $language, string $direction = 'asc')
 * @method static Builder<static>|WidgetAsset query()
 * @method static Builder<static>|WidgetAsset withAssets()
 *
 * @property-read Collection<int, AssetRelation> $assetRelations
 * @property-read int|null $asset_relations_count
 * @property-read Collection<int, AssetRelation> $assets
 * @property-read int|null $assets_count
 *
 * @mixin Model
 *
 * @method static Builder<static>|WidgetAsset whereAssetId($value)
 * @method static Builder<static>|WidgetAsset whereAssetType($value)
 * @method static Builder<static>|WidgetAsset whereContainer($value)
 * @method static Builder<static>|WidgetAsset whereCreatedAt($value)
 * @method static Builder<static>|WidgetAsset whereCreatedBy($value)
 * @method static Builder<static>|WidgetAsset whereDeletedBy($value)
 * @method static Builder<static>|WidgetAsset whereId($value)
 * @method static Builder<static>|WidgetAsset whereMeta($value)
 * @method static Builder<static>|WidgetAsset whereOccurrence($value)
 * @method static Builder<static>|WidgetAsset whereOrder($value)
 * @method static Builder<static>|WidgetAsset wherePageId($value)
 * @method static Builder<static>|WidgetAsset whereUpdatedAt($value)
 * @method static Builder<static>|WidgetAsset whereUpdatedBy($value)
 * @method static Builder<static>|WidgetAsset whereWidgetId($value)
 *
 * @mixin Model
 */
class WidgetAsset extends Model implements HasMedia, PageCacheable, Userstampable
{
    use ComposhipsJsonRelationshipsTrait;
    use HasAssets;

    /** @use HasFactory<WidgetAssetFactory> */
    use HasFactory;

    use HasMetaData;
    use HasUserstamps;
    use InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'container',
        'pageable_type',
        'pageable_id',
        'meta',
        'occurrence',
        'order',
        'asset_id',
        'asset_type',
        'widget_id',
    ];

    protected static string $factory = WidgetAssetFactory::class;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::Image->value)->singleFile();
    }

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    public function pageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function asset(): MorphTo
    {
        return $this->morphTo();
    }

    public function linkedPage(): MorphTo
    {
        return $this->morphTo('meta->linked_pageable_type', 'meta->linked_pageable_id');
    }

    protected function getAssetKeyAttribute(): string
    {
        return $this->asset_type . '.' . $this->asset_id;
    }

    protected function scopeOrdered(Builder $query, string $dir = 'asc'): void
    {
        $query->orderBy($this->qualifyColumn('order'), $dir);
    }

    protected function scopeAlphabetical(Builder $query, Language $language, string $direction = 'asc'): void
    {
        $query->orderBy(
            Translation::query()->select('title')
                ->whereColumn('translatable_id', $this->qualifyColumn('asset_id'))
                ->whereColumn('translatable_type', $this->qualifyColumn('asset_type'))
                ->where('language_id', $language->id),
            $direction,
        );
    }

    protected function casts(): array
    {
        return [
            'meta' => 'json',
            'order' => 'integer',
            'occurrence' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
