<?php

declare(strict_types=1);

namespace Capell\Layout\Models;

use Bkwld\Cloner\Cloneable;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Enums\PublishStatusEnum;
use Capell\Core\Models\AssetRelation;
use Capell\Core\Models\Concerns\HasAssets;
use Capell\Core\Models\Concerns\HasDraftsAndNestedSet;
use Capell\Core\Models\Concerns\HasMetaData;
use Capell\Core\Models\Concerns\HasMorphModelRelations;
use Capell\Core\Models\Concerns\HasPublishDates;
use Capell\Core\Models\Concerns\HasTranslations;
use Capell\Core\Models\Concerns\HasType;
use Capell\Core\Models\Concerns\HasTypes;
use Capell\Core\Models\Concerns\HasUserstamps;
use Capell\Core\Models\Concerns\InteractsWithMedia;
use Capell\Core\Models\Contracts\Draftable;
use Capell\Core\Models\Contracts\HasDraftsAndNestedSetModel;
use Capell\Core\Models\Contracts\Publishable;
use Capell\Core\Models\Contracts\Typeable;
use Capell\Core\Models\Contracts\Userstampable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Capell\Layout\Database\Factories\ContentFactory;
use Capell\Layout\Observers\ContentObserver;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Kalnoy\Nestedset\NodeTrait;
use Kalnoy\Nestedset\QueryBuilder as NestedQueryBuilder;
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

/**
 * @property-read Collection<int, AssetRelation> $assets
 * @property-read int|null $assets_count
 * @property-read int|null $audits_count
 * @property-read \Kalnoy\Nestedset\Collection<int, Content> $children
 * @property-read int|null $children_count
 * @property-read User|null $creator
 * @property-read User|null $destroyer
 * @property-read User|null $editor
 * @property-read array $actions
 * @property-read mixed $draft
 * @property-read PublishStatusEnum $publish_status
 * @property-read Content|null $hasDraftsAndNestedSetParent
 * @property-read Media|null $image
 * @property-read Collection<int, Language> $languages
 * @property-read int|null $languages_count
 * @property-read Content|null $nodeTraitParent
 * @property-read Page|null $page
 * @property-read \Kalnoy\Nestedset\Collection<int, Page> $pages
 * @property-read int|null $pages_count
 * @property-read Content|null $parent
 * @property-read Model $publisher
 * @property-read \Kalnoy\Nestedset\Collection<int, Content> $revisions
 * @property-read int|null $revisions_count
 * @property-write mixed $parent_id
 * @property-read Site|null $site
 * @property-read Translation|null $translation
 * @property-read Collection<int, Translation> $translations
 * @property-read int|null $translations_count
 * @property-read Type|null $type
 * @property-read Collection<int, Widget> $widgets
 * @property-read int|null $widgets_count
 * @property-read Collection|Media[] $media
 * @property-read int|null $media_count
 * @property-read Collection|Content[] $related
 * @property-read int|null $related_count
 *
 * @method static \Kalnoy\Nestedset\Collection<int, static> all($columns = ['*'])
 * @method static QueryBuilder<static>|Content ancestorsAndSelf($id, array $columns = [])
 * @method static QueryBuilder<static>|Content ancestorsOf($id, array $columns = [])
 * @method static QueryBuilder<static>|Content applyNestedSetScope(?string $table = null)
 * @method static QueryBuilder<static>|Content countErrors()
 * @method static QueryBuilder<static>|Content current()
 * @method static QueryBuilder<static>|Content d()
 * @method static QueryBuilder<static>|Content defaultOrder(string $dir = 'asc')
 * @method static QueryBuilder<static>|Content descendantsAndSelf($id, array $columns = [])
 * @method static QueryBuilder<static>|Content descendantsOf($id, array $columns = [], $andSelf = false)
 * @method static QueryBuilder<static>|Content excludeRevision(Model|int $exclude)
 * @method static QueryBuilder<static>|Content expired(Model $model)
 * @method static ContentFactory factory($count = null, $state = [])
 * @method static QueryBuilder<static>|Content fixSubtree($root)
 * @method static QueryBuilder<static>|Content fixTree($root = null)
 * @method static \Kalnoy\Nestedset\Collection<int, static> get($columns = ['*'])
 * @method static QueryBuilder<static>|Content getNodeData($id, $required = false)
 * @method static QueryBuilder<static>|Content getPlainNodeData($id, $required = false)
 * @method static QueryBuilder<static>|Content getTotalErrors()
 * @method static QueryBuilder<static>|Content hasChildren()
 * @method static QueryBuilder<static>|Content hasParent()
 * @method static QueryBuilder<static>|Content isBroken()
 * @method static QueryBuilder<static>|Content leaves(array $columns = [])
 * @method static QueryBuilder<static>|Content makeGap(int $cut, int $height)
 * @method static QueryBuilder<static>|Content moveNode($key, $position)
 * @method static QueryBuilder<static>|Content newModelQuery()
 * @method static QueryBuilder<static>|Content newQuery()
 * @method static Builder<static>|Content onlyTrashed()
 * @method static QueryBuilder<static>|Content orWhereAncestorOf(bool $id, bool $andSelf = false)
 * @method static QueryBuilder<static>|Content orWhereDescendantOf($id)
 * @method static QueryBuilder<static>|Content orWhereNodeBetween($values)
 * @method static QueryBuilder<static>|Content orWhereNotDescendantOf($id)
 * @method static QueryBuilder<static>|Content ordered(string $dir = 'asc')
 * @method static QueryBuilder<static>|Content pending(Model $model)
 * @method static QueryBuilder<static>|Content published(Model $model)
 * @method static QueryBuilder<static>|Content query()
 * @method static QueryBuilder<static>|Content rebuildSubtree($root, array $data, $delete = false)
 * @method static QueryBuilder<static>|Content rebuildTree(array $data, $delete = false, $root = null)
 * @method static QueryBuilder<static>|Content reversed()
 * @method static QueryBuilder<static>|Content root(array $columns = [])
 * @method static QueryBuilder<static>|Content whereAncestorOf($id, $andSelf = false, $boolean = 'and')
 * @method static QueryBuilder<static>|Content whereAncestorOrSelf($id)
 * @method static QueryBuilder<static>|Content whereDescendantOf($id, $boolean = 'and', $not = false, $andSelf = false)
 * @method static QueryBuilder<static>|Content whereDescendantOrSelf(string $id, string $boolean = 'and', string $not = false)
 * @method static QueryBuilder<static>|Content whereIsAfter($id, $boolean = 'and')
 * @method static QueryBuilder<static>|Content whereIsBefore($id, $boolean = 'and')
 * @method static QueryBuilder<static>|Content whereIsLeaf()
 * @method static QueryBuilder<static>|Content whereIsRoot()
 * @method static QueryBuilder<static>|Content whereNodeBetween($values, $boolean = 'and', $not = false, $query = null)
 * @method static QueryBuilder<static>|Content whereNotDescendantOf($id)
 * @method static QueryBuilder<static>|Content withDepth(string $as = 'depth')
 * @method static QueryBuilder<static>|Content withAssets(bool $withDrafts = true)
 * @method static Builder<static>|Content withTrashed()
 * @method static QueryBuilder<static>|Content withWhereHasLanguage(int $language_id)
 * @method static QueryBuilder<static>|Content withoutCurrent()
 * @method static QueryBuilder<static>|Content withoutRoot()
 * @method static QueryBuilder<static>|Content withoutSelf()
 * @method static Builder<static>|Content withoutTrashed()
 *
 * @property-read Page|null $linkedPage
 * @property-read Collection<int, AssetRelation> $assetRelations
 * @property-read int|null $asset_relations_count
 * @property-read Collection<int, Activity> $activities
 * @property-read int|null $activities_count
 * @property-read string|null $title
 * @property-read Collection<int, WidgetAsset> $widgetAssets
 * @property-read int|null $widget_assets_count
 *
 * @mixin Model
 *
 * @property int $id
 * @property string $name
 * @property int $type_id
 * @property int|null $site_id
 * @property array<array-key, mixed>|null $meta
 * @property int $order
 * @property CarbonImmutable|null $publish_from
 * @property CarbonImmutable|null $publish_to
 * @property string|null $uuid
 * @property CarbonImmutable|null $published_at
 * @property bool $is_published
 * @property bool $is_current
 * @property string|null $publisher_type
 * @property int|null $publisher_id
 * @property int $_lft
 * @property int $_rgt
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read \Kalnoy\Nestedset\Collection<int, Content> $drafts
 * @property-read int|null $drafts_count
 *
 * @method static NestedQueryBuilder<static>|Content whereCreatedAt($value)
 * @method static NestedQueryBuilder<static>|Content whereCreatedBy($value)
 * @method static NestedQueryBuilder<static>|Content whereDeletedAt($value)
 * @method static NestedQueryBuilder<static>|Content whereDeletedBy($value)
 * @method static NestedQueryBuilder<static>|Content whereId($value)
 * @method static NestedQueryBuilder<static>|Content whereIsCurrent($value)
 * @method static NestedQueryBuilder<static>|Content whereIsPublished($value)
 * @method static NestedQueryBuilder<static>|Content whereLft($value)
 * @method static NestedQueryBuilder<static>|Content whereMeta($value)
 * @method static NestedQueryBuilder<static>|Content whereName($value)
 * @method static NestedQueryBuilder<static>|Content whereOrder($value)
 * @method static NestedQueryBuilder<static>|Content whereParentId($value)
 * @method static NestedQueryBuilder<static>|Content wherePublishFrom($value)
 * @method static NestedQueryBuilder<static>|Content wherePublishTo($value)
 * @method static NestedQueryBuilder<static>|Content wherePublishedAt($value)
 * @method static NestedQueryBuilder<static>|Content wherePublisherId($value)
 * @method static NestedQueryBuilder<static>|Content wherePublisherType($value)
 * @method static NestedQueryBuilder<static>|Content whereRgt($value)
 * @method static NestedQueryBuilder<static>|Content whereSiteId($value)
 * @method static NestedQueryBuilder<static>|Content whereTypeId($value)
 * @method static NestedQueryBuilder<static>|Content whereUpdatedAt($value)
 * @method static NestedQueryBuilder<static>|Content whereUpdatedBy($value)
 * @method static NestedQueryBuilder<static>|Content whereUuid($value)
 *
 * @mixin Model
 */
#[ObservedBy(ContentObserver::class)]
class Content extends Model implements Draftable, HasDraftsAndNestedSetModel, HasMedia, PageCacheable, Publishable, Typeable, Userstampable
{
    use Cloneable;
    use HasAssets;
    use HasDrafts {
        bootHasDrafts as protected;
    }
    use HasDraftsAndNestedSet {
        HasDraftsAndNestedSet::parent as hasDraftsAndNestedSetParent;
    }
    use HasFactory;
    use HasJsonRelationships;
    use HasMetaData;
    use HasMorphModelRelations;
    use HasPublishDates;
    use HasTranslations;
    use HasType;
    use HasTypes;
    use HasUserstamps;
    use InteractsWithMedia;
    use LogsActivity;
    use NodeTrait {
        NodeTrait::bootNodeTrait as protected;
        NodeTrait::parent as nodeTraitParent;
        NodeTrait::applyNestedSetScope as applyNestedSetScopeParent;
        NodeTrait::newScopedQuery as nodeTraitNewScopedQuery;
        NodeTrait::setParentIdAttribute as nodeTraitSetParentIdAttribute;
    }
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'is_published',
        'meta',
        'name',
        'order',
        'parent_id',
        'publish_from',
        'publish_to',
        'site_id',
        'type_id',
        'uuid',
    ];

    /**
     * Relations on this model that should be cloned
     *
     * @var array|string[]
     */
    protected array $cloneable_relations = [
        'translations',
    ];

    protected static string $factory = ContentFactory::class;

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
                'draft_id',
                'is_published',
                'is_current',
                'publisher_type',
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

    public function getPreviousRevision(): HasOne
    {
        return $this->hasOne(static::class, $this->getKeyName())
            ->withDrafts()
            ->latestOfMany();
    }

    public function parent(): BelongsTo
    {
        return $this->hasDraftsAndNestedSetParent();
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
        return $this->morphTo(null, 'meta->linked_pageable_type', 'meta->linked_pageable_id');
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

    /**
     * Public bridge to NodeTrait's protected callPendingAction.
     */
    public function nodeCallPendingAction(): void
    {
        $this->callPendingAction();
    }

    /**
     * Public bridge to NodeTrait's protected refreshNode.
     */
    public function nodeRefreshNode(): void
    {
        $this->refreshNode();
    }

    /**
     * Public bridge to NodeTrait's protected deleteDescendants.
     */
    public function nodeDeleteDescendants(): void
    {
        $this->deleteDescendants();
    }

    /**
     * Public bridge to NodeTrait's protected restoreDescendants.
     */
    public function restoreDescendants(mixed $deletedAt): void
    {
        $this->restoreDescendants($deletedAt);
    }

    /**
     * Helper to expose deleted_at value.
     */
    public function nodeGetDeletedAtValue(): mixed
    {
        return $this->getAttribute($this->getDeletedAtColumn());
    }

    protected static function bootNodeTrait(): void
    {
        // Handled in observer
    }

    protected static function bootHasDrafts(): void
    {
        // Handled in observer
    }

    /**
     * Ensure nested set operations include drafts when resolving parents/ancestors.
     */
    protected function newScopedQuery(): NestedQueryBuilder
    {
        /** @var NestedQueryBuilder $query */
        $query = $this->nodeTraitNewScopedQuery();

        return $query->withDrafts();
    }

    protected function scopeOrdered(Builder $query, string $dir = 'asc'): void
    {
        $query->orderBy($this->qualifyColumn('order'))
            ->orderBy($this->qualifyColumn('name'));
    }

    protected function actions(): Attribute
    {
        return Attribute::make(get: fn () => $this->meta['actions'] ?? []);
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'meta' => 'json',
            'publish_from' => 'datetime',
            'publish_to' => 'datetime',
        ];
    }
}
