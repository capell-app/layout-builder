<?php

declare(strict_types=1);

namespace Capell\Layout\Models;

use Bkwld\Cloner\Cloneable;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Models\Concerns\HasAssets;
use Capell\Core\Models\Concerns\HasDraftsAndNestedSet;
use Capell\Core\Models\Concerns\HasMetaData;
use Capell\Core\Models\Concerns\HasPageCache;
use Capell\Core\Models\Concerns\HasPublishDates;
use Capell\Core\Models\Concerns\HasTags;
use Capell\Core\Models\Concerns\HasTranslations;
use Capell\Core\Models\Concerns\HasTypes;
use Capell\Core\Models\Language;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Tag;
use Capell\Core\Models\Type;
use Capell\Layout\Database\Factories\ContentFactory;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Observers\ContentObserver;
use Eloquent;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;
use OwenIt\Auditing\Contracts\Auditable;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;
use Wildside\Userstamps\Userstamps;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ContentAsset> $assets
 * @property-read int|null $assets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \OwenIt\Auditing\Models\Audit> $audits
 * @property-read int|null $audits_count
 * @property-read \Kalnoy\Nestedset\Collection<int, Content> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Foundation\Auth\User|null $creator
 * @property-read \Illuminate\Foundation\Auth\User|null $destroyer
 * @property-read \Illuminate\Foundation\Auth\User|null $editor
 * @property-read array $actions
 * @property-read mixed $draft
 * @property-read \Capell\Core\Enums\PublishStatusEnum $publish_status
 * @property-read Content|null $hasDraftsAndNestedSetParent
 * @property-read Media|null $image
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Language> $languages
 * @property-read int|null $languages_count
 * @property-read Content|null $nodeTraitParent
 * @property-read Page|null $page
 * @property-read \Kalnoy\Nestedset\Collection<int, Page> $pages
 * @property-read int|null $pages_count
 * @property-read Content|null $parent
 * @property-read Model|Eloquent $publisher
 * @property-read \Kalnoy\Nestedset\Collection<int, Content> $revisions
 * @property-read int|null $revisions_count
 * @property-write mixed $parent_id
 * @property \Illuminate\Database\Eloquent\Collection<int, Tag> $tags
 * @property-read Site|null $site
 * @property-read int|null $tags_count
 * @property-read \Capell\Core\Models\Translation|null $translation
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Capell\Core\Models\Translation> $translations
 * @property-read int|null $translations_count
 * @property-read Type|null $type
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Widget> $widgets
 * @property-read int|null $widgets_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Media[] $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Content[] $related
 * @property-read int|null $related_count
 *
 * @method static \Kalnoy\Nestedset\Collection<int, static> all($columns = ['*'])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content ancestorsAndSelf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content ancestorsOf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content applyNestedSetScope(?string $table = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content countErrors()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content current()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content d()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content defaultOrder(string $dir = 'asc')
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content descendantsAndSelf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content descendantsOf($id, array $columns = [], $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content excludeRevision(\Illuminate\Database\Eloquent\Model|int $exclude)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content expired(\Illuminate\Database\Eloquent\Model $model)
 * @method static \Capell\Layout\Database\Factories\ContentFactory factory($count = null, $state = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content fixSubtree($root)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content fixTree($root = null)
 * @method static \Kalnoy\Nestedset\Collection<int, static> get($columns = ['*'])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content getNodeData($id, $required = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content getPlainNodeData($id, $required = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content getTotalErrors()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content hasChildren()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content hasParent()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content isBroken()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content leaves(array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content makeGap(int $cut, int $height)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content moveNode($key, $position)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content newModelQuery()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content newQuery()
 * @method static Builder<static>|Content onlyTrashed()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content orWhereAncestorOf(bool $id, bool $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content orWhereDescendantOf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content orWhereNodeBetween($values)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content orWhereNotDescendantOf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content ordered(string $dir = 'asc')
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content pending(\Illuminate\Database\Eloquent\Model $model)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content published(\Illuminate\Database\Eloquent\Model $model)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content query()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content rebuildSubtree($root, array $data, $delete = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content rebuildTree(array $data, $delete = false, $root = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content reversed()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content root(array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content whereAncestorOf($id, $andSelf = false, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content whereAncestorOrSelf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content whereDescendantOf($id, $boolean = 'and', $not = false, $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content whereDescendantOrSelf(string $id, string $boolean = 'and', string $not = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content whereIsAfter($id, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content whereIsBefore($id, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content whereIsLeaf()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content whereIsRoot()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content whereNodeBetween($values, $boolean = 'and', $not = false, $query = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content whereNotDescendantOf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content withAllTags(\ArrayAccess|\Spatie\Tags\Tag|array|string $tags, ?string $type = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content withAllTagsOfAnyType($tags)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content withAnyTags(\ArrayAccess|\Spatie\Tags\Tag|array|string $tags, ?string $type = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content withAnyTagsOfAnyType($tags)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content withAnyTagsOfType(array|string $type)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content withDepth(string $as = 'depth')
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content withAssets(bool $withDrafts = true)
 * @method static Builder<static>|Content withTrashed()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content withWhereHasLanguage(int $language_id)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content withoutCurrent()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content withoutRoot()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content withoutSelf()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Content withoutTags(\ArrayAccess|\Spatie\Tags\Tag|array|string $tags, ?string $type = null)
 * @method static Builder<static>|Content withoutTrashed()
 *
 * @mixin \Eloquent
 *
 * @property-read Page|null $linkedPage
 *
 * @mixin Eloquent
 */
#[ObservedBy(ContentObserver::class)]
class Content extends Model implements Auditable, PageCacheable
{
    use Cloneable;
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
    use HasPageCache;
    use HasPublishDates;
    use HasTags;
    use HasTranslations;
    use HasTypes;
    use NodeTrait {
        NodeTrait::bootNodeTrait as protected;
        NodeTrait::parent as nodeTraitParent;
        NodeTrait::applyNestedSetScope as applyNestedSetScopeParent;
    }
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;
    use Userstamps;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'meta',
        'parent_id',
        'parent_uuid',
        'publish_from',
        'publish_to',
        'is_published',
        'order',
        'site_id',
        'type_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'publish_from' => 'datetime',
        'publish_to' => 'datetime',
        'meta' => 'json',
    ];

    /**
     * Relations on this model that should be cloned
     *
     * @var array|string[]
     */
    protected array $cloneable_relations = [
        'tags',
        'translations',
    ];

    protected static string $factory = ContentFactory::class;

    public static function findByUuid(string $value, $select = '*'): ?static
    {
        return static::where('uuid', $value)->select($select)->first();
    }

    public static function getMorphRelations(): array
    {
        return ['image', 'type', 'translation'];
    }

    public function applyNestedSetScope($query, $table = null)
    {
        $builder = $this->usesSoftDelete()
            ? $this->withTrashed()
            : $this->newQuery();

        $builder->withDrafts();

        $builder->where($table ? $table.'.'.$this->getIsPublishedColumn() : $this->getIsPublishedColumn(), true);

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
        return $table ? $table.'.'.$this->getIsPublishedColumn() : $this->getIsPublishedColumn();
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

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'meta->image_id');
    }

    public function linkedPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'meta->page_uuid', 'uuid');
    }

    public function media(): BelongsToJson
    {
        return $this->belongsToJson(Media::class, 'meta->media');
    }

    public function related(): BelongsToJson
    {
        return $this->belongsToJson(self::class, 'meta->related');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(ContentAsset::class);
    }

    public function widgets(): HasManyThrough
    {
        return $this->hasManyThrough(Widget::class, WidgetAsset::class, 'asset_id', 'id', 'id', 'widget_id')
            ->where('asset_type', $this->getMorphClass())
            ->whereNull('widget_assets.page_id');
    }

    public function pages(): HasManyThrough
    {
        return $this->hasManyThrough(Page::class, WidgetAsset::class, 'asset_id', 'id', 'id', 'page_id')
            ->where('asset_type', $this->getMorphClass())
            ->whereNotNull('widget_assets.page_id')
            ->groupBy('pages.id');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'taggables');
    }

    public function scopeOrdered(Builder $query, string $dir = 'asc'): void
    {
        $query->orderBy($this->qualifyColumn('order'))
            ->orderBy($this->qualifyColumn('name'));
    }

    protected static function bootNodeTrait(): void
    {
        // Handled in boot
    }

    protected static function bootHasDrafts(): void
    {
        // Handled in boot
    }

    protected function getActionsAttribute(): array
    {
        return $this->meta['actions'] ?? [];
    }
}
