<?php

declare(strict_types=1);

namespace Capell\Layout\Models;

use Aimeos\Nestedset\NodeTrait;
use Aimeos\Nestedset\QueryBuilder;
use Bkwld\Cloner\Cloneable;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Enums\PublishStatusEnum;
use Capell\Core\Models\AssetRelation;
use Capell\Core\Models\Concerns\HasAssets;
use Capell\Core\Models\Concerns\HasMetaData;
use Capell\Core\Models\Concerns\HasMorphModelRelations;
use Capell\Core\Models\Concerns\HasPublishDates;
use Capell\Core\Models\Concerns\HasTranslations;
use Capell\Core\Models\Concerns\HasType;
use Capell\Core\Models\Concerns\HasTypes;
use Capell\Core\Models\Concerns\HasUserstamps;
use Capell\Core\Models\Concerns\InteractsWithMedia;
use Capell\Core\Models\Contracts\Publishable;
use Capell\Core\Models\Contracts\Typeable;
use Capell\Core\Models\Contracts\Userstampable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Capell\Core\Workspaces\BelongsToWorkspace;
use Capell\Layout\Database\Factories\CollectionFactory;
use Capell\Layout\Models\Concerns\ComposhipsJsonRelationshipsTrait;
use Capell\Layout\Observers\CollectionObserver;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

/**
 * @property-read EloquentCollection<int, AssetRelation> $assets
 * @property-read int|null $assets_count
 * @property-read int|null $audits_count
 * @property-read \Aimeos\Nestedset\Collection<int, self> $children
 * @property-read int|null $children_count
 * @property-read User|null $creator
 * @property-read User|null $destroyer
 * @property-read User|null $editor
 * @property-read array $actions
 * @property-read PublishStatusEnum $publish_status
 * @property-read Media|null $image
 * @property-read EloquentCollection<int, Language> $languages
 * @property-read int|null $languages_count
 * @property-read Pageable|null $page
 * @property-read \Aimeos\Nestedset\Collection<int, Pageable> $pages
 * @property-read int|null $pages_count
 * @property-read self|null $parent
 * @property-write mixed $parent_id
 * @property-read Site|null $site
 * @property-read Translation|null $translation
 * @property-read EloquentCollection<int, Translation> $translations
 * @property-read int|null $translations_count
 * @property-read Type|null $type
 * @property-read EloquentCollection<int, Widget> $widgets
 * @property-read int|null $widgets_count
 * @property-read EloquentCollection|Media[] $media
 * @property-read int|null $media_count
 * @property-read EloquentCollection|self[] $related
 * @property-read int|null $related_count
 * @property-read Page|null $linkedPage
 * @property-read EloquentCollection<int, AssetRelation> $assetRelations
 * @property-read int|null $asset_relations_count
 * @property-read EloquentCollection<int, Activity> $activities
 * @property-read int|null $activities_count
 * @property-read string|null $title
 * @property-read EloquentCollection<int, WidgetAsset> $widgetAssets
 * @property-read int|null $widget_assets_count
 * @property int $id
 * @property int $workspace_id
 * @property int $shadowed_by_workspace_id
 * @property string $name
 * @property int $type_id
 * @property int|null $site_id
 * @property array<array-key, mixed>|null $meta
 * @property int $order
 * @property CarbonImmutable|null $visible_from
 * @property CarbonImmutable|null $visible_until
 * @property int $_lft
 * @property int $_rgt
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 *
 * @mixin Model
 * @mixin QueryBuilder
 */
#[ObservedBy(CollectionObserver::class)]
class Collection extends Model implements HasMedia, PageCacheable, Publishable, Typeable, Userstampable
{
    use BelongsToWorkspace;
    use Cloneable;
    use ComposhipsJsonRelationshipsTrait;
    use HasAssets;
    use HasFactory;
    use HasMetaData;
    use HasMorphModelRelations;
    use HasPublishDates;
    use HasTranslations;
    use HasType;
    use HasTypes;
    use HasUserstamps;
    use InteractsWithMedia;
    use LogsActivity;
    use NodeTrait;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'meta',
        'name',
        'order',
        'parent_id',
        'visible_from',
        'visible_until',
        'site_id',
        'type_id',
    ];

    /**
     * Relations on this model that should be cloned
     *
     * @var array|string[]
     */
    protected array $cloneable_relations = [
        'translations',
    ];

    protected $table = 'collections';

    protected static string $factory = CollectionFactory::class;

    public static function getMorphRelations(?Language $language = null, bool $normalizeKey = false): array
    {
        $base = [
            'ancestors.type',
            'image',
            'media',
            'linkedPage' => function (BuilderContract $query) use ($language): void {
                $query->with([
                    'translation' => function (BuilderContract $query) use ($language): void {
                        $query->with('language')
                            ->when(
                                $language,
                                function (BuilderContract $query) use ($language): void {
                                    if (DB::getDriverName() === 'sqlite') {
                                        $query->orderByRaw(
                                            'CASE language_id '
                                            . sprintf('WHEN %d THEN 0 ELSE 1 END', $language->id),
                                        );
                                    } else {
                                        $query->orderByRaw('FIELD(language_id, ?)', [$language->id ?? 0]);
                                    }
                                },
                            );
                    },
                    'pageUrl' => function (BuilderContract $query) use ($language): void {
                        $query->with('siteDomain')
                            ->when(
                                $language,
                                function (BuilderContract $query) use ($language): void {
                                    if (DB::getDriverName() === 'sqlite') {
                                        $query->orderByRaw(
                                            'CASE language_id '
                                            . sprintf('WHEN %d THEN 0 ELSE 1 END', $language->id),
                                        );
                                    } else {
                                        $query->orderByRaw('FIELD(language_id, ?)', [$language->id ?? 0]);
                                    }
                                },
                            );
                    },
                ]);
            },
            'translation' => fn (BuilderContract $query): BuilderContract => $query->with('language')
                ->when($language, fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id)),
            'type',
        ];

        return static::mergeMorphRelationDefinitions($base, self::class, $language, $normalizeKey);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('content')
            ->logAll()
            ->logExcept([
                'updated_at',
                'created_at',
                'deleted_at',
                'workspace_id',
                'shadowed_by_workspace_id',
                '_lft',
                '_rgt',
                'created_by',
                'updated_by',
                'deleted_by',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::Image->value)->singleFile();
    }

    public function loadParent(Language $language): void
    {
        $this->load([
            'parent' => fn (BuilderContract $query): BuilderContract => $query->withWhereHasLanguage($language->id),
        ]);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function image(): MorphOne
    {
        return $this->morphOneMedia(MediaCollectionEnum::Image->value);
    }

    public function linkedPage(): MorphTo
    {
        return $this->morphTo(type: 'meta->linked_pageable_type', id: 'meta->linked_pageable_id');
    }

    public function related(): BelongsToJson
    {
        return $this->belongsToJson(self::class, 'meta->related');
    }

    public function widgetAssets(): HasMany
    {
        return $this->hasMany(WidgetAsset::class, 'asset_id')
            ->where('asset_type', $this->getMorphClass());
    }

    public function pages(): HasMany
    {
        return $this->widgetAssets()
            ->select('widget_assets.pageable_type', 'widget_assets.pageable_id')
            ->whereNotNull('widget_assets.pageable_type')
            ->whereNotNull('widget_assets.pageable_id')
            ->groupBy('widget_assets.pageable_type', 'widget_assets.pageable_id');
    }

    public function widgets(): HasMany
    {
        return $this->widgetAssets()
            ->select('widget_assets.widget_id')
            ->groupBy('widget_assets.widget_id');
    }

    protected function scopeOrdered(Builder $query, string $dir = 'asc'): void
    {
        $query->orderBy($this->qualifyColumn('order'))
            ->orderBy($this->qualifyColumn('name'));
    }

    protected function getActionsAttribute(): array
    {
        return $this->meta['actions'] ?? [];
    }

    protected function casts(): array
    {
        return [
            'meta' => 'json',
            'visible_from' => 'datetime',
            'visible_until' => 'datetime',
        ];
    }
}
