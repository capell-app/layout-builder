<?php

declare(strict_types=1);

namespace Capell\Layout\Models;

use Bkwld\Cloner\Cloneable;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Enums\PublishStatusEnum;
use Capell\Core\Models\AssetRelation;
use Capell\Core\Models\Concerns\CloneableExcept;
use Capell\Core\Models\Concerns\HasAssets;
use Capell\Core\Models\Concerns\HasDraftsAndNestedSet;
use Capell\Core\Models\Concerns\HasMetaData;
use Capell\Core\Models\Concerns\HasModelRelations;
use Capell\Core\Models\Concerns\HasPageCache;
use Capell\Core\Models\Concerns\HasPublishDates;
use Capell\Core\Models\Concerns\HasTranslations;
use Capell\Core\Models\Concerns\HasTypes;
use Capell\Core\Models\Concerns\InteractsWithMedia;
use Capell\Core\Models\Contracts\Draftable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Capell\Layout\Database\Factories\ContentFactory;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Observers\ContentObserver;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;
use Kalnoy\Nestedset\NodeTrait;
use Kalnoy\Nestedset\QueryBuilder;
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;
use Wildside\Userstamps\Userstamps;

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
 */
#[ObservedBy(ContentObserver::class)]
class Content extends Model implements Draftable, HasMedia, PageCacheable
{
    use Cloneable;
    use CloneableExcept;
    use HasAssets;
    use HasDrafts {
        bootHasDrafts as protected;
    }
    use HasDraftsAndNestedSet {
        HasDraftsAndNestedSet::parent as hasDraftsAndNestedSetParent;
    }

    /** @use HasFactory<ContentFactory> */
    use HasFactory;

    use HasJsonRelationships;
    use HasMetaData;
    use HasModelRelations;
    use HasPageCache;
    use HasPublishDates;
    use HasTranslations;
    use HasTypes;
    use InteractsWithMedia;
    use LogsActivity;
    use NodeTrait {
        NodeTrait::bootNodeTrait as protected;
        NodeTrait::parent as nodeTraitParent;
        NodeTrait::applyNestedSetScope as applyNestedSetScopeParent;
    }
    use SoftDeletes;
    use Userstamps;

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

    protected $casts = [
        'is_published' => 'boolean',
        'meta' => 'json',
        'publish_from' => 'datetime',
        'publish_to' => 'datetime',
    ];

    protected static string $factory = ContentFactory::class;

    public static function getMorphRelations(?Language $language = null): array
    {
        $base = [
            'ancestors.type',
            'image',
            'media',
            'linkedPage' => fn (BuilderContract $query) => $query->with([
                'translation' => fn (BuilderContract $query) => $query->with('language')
                    ->when($language, fn ($q) => $q->where('language_id', $language->id)),
                'pageUrl' => fn (BuilderContract $query) => $query->with('siteDomain')
                    ->when($language, fn ($q) => $q->where('language_id', $language->id)),
            ]),
            'translation' => fn (BuilderContract $query) => $query->with('language')
                ->when($language, fn ($q) => $q->where('language_id', $language->id)),
            'related' => fn (BuilderContract $query) => $query->with([
                'image',
                'page' => fn (BuilderContract $query) => $query->with([
                    'translation' => fn (BuilderContract $query) => $query->with('language')
                        ->when($language, fn ($q) => $q->where('language_id', $language->id)),
                    'pageUrl' => fn (BuilderContract $query) => $query->with('siteDomain')
                        ->when($language, fn ($q) => $q->where('language_id', $language->id)),
                    'site',
                ]),
            ])
                ->withWhereHas('translation', fn (BuilderContract $query) => $query->with('language')),
            'type',
        ];

        return static::mergeMorphRelationDefinitions($base, self::class, $language);
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
                'publisher_id',
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

    public function applyNestedSetScope($query, $table = null)
    {
        $builder = $this->usesSoftDelete()
            ? $this->withTrashed()
            : $this->newQuery();

        $builder->withDrafts();

        $builder->where($table ? $table . '.' . $this->getIsPublishedColumn() : $this->getIsPublishedColumn(), true);

        return $this->applyNestedSetScopeParent($query, $table);
    }

    public function newNestedSetQuery($table = null)
    {
        $builder = $this->usesSoftDelete()
            ? $this->withTrashed()
            : $this->newQuery();

        $builder->withDrafts();

        return $this->applyNestedSetScope($builder);
    }

    public function getQualifiedIsPublishedColumn(?string $table = null): string
    {
        return in_array($table, [null, '', '0'], true) ? $this->getIsPublishedColumn() : $table . '.' . $this->getIsPublishedColumn();
    }

    public function loadParent(Language $language): void
    {
        $this->load([
            'parent' => fn (BuilderContract $query) => $query->withWhereHasLanguage($language->id),
        ]);
    }

    public function parent(): BelongsTo
    {
        return $this->hasDraftsAndNestedSetParent();
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class)
            ->where('type', LayoutTypeEnum::Content);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function image(): MorphOne
    {
        return $this->morphOneMedia(MediaCollectionEnum::Image->value);
    }

    public function linkedPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'meta->page_id', 'id');
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
            ->select('widget_assets.page_id')
            ->whereNotNull('widget_assets.page_id')
            ->groupBy('widget_assets.page_id');
    }

    public function widgets(): HasMany
    {
        return $this->widgetAssets()
            ->select('widget_assets.widget_id')
            ->groupBy('widget_assets.widget_id');
    }

    protected static function bootNodeTrait(): void
    {
        // Handled in boot
    }

    protected static function bootHasDrafts(): void
    {
        // Handled in boot
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
}
