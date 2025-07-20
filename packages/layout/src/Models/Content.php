<?php

declare(strict_types=1);

namespace Capell\Layout\Models;

use ArrayAccess;
use Bkwld\Cloner\Cloneable;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Enums\PublishStatusEnum;
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
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Capell\Layout\Database\Factories\ContentFactory;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Observers\ContentObserver;
use Eloquent;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;
use Kalnoy\Nestedset\NodeTrait;
use Kalnoy\Nestedset\QueryBuilder;
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Models\Audit;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;
use Wildside\Userstamps\Userstamps;

/**
 * @property-read Collection<int, ContentAsset> $assets
 * @property-read int|null $assets_count
 * @property-read Collection<int, Audit> $audits
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
 * @property-read Model|Eloquent $publisher
 * @property-read \Kalnoy\Nestedset\Collection<int, Content> $revisions
 * @property-read int|null $revisions_count
 * @property-write mixed $parent_id
 * @property Collection<int, Tag> $tags
 * @property-read Site|null $site
 * @property-read int|null $tags_count
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
 * @method static QueryBuilder<static>|Content excludeRevision((Model|int) $exclude)
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
 * @method static QueryBuilder<static>|Content withAllTags((ArrayAccess|\Spatie\Tags\Tag|array|string) $tags, ?string $type = null)
 * @method static QueryBuilder<static>|Content withAllTagsOfAnyType($tags)
 * @method static QueryBuilder<static>|Content withAnyTags((ArrayAccess|\Spatie\Tags\Tag|array|string) $tags, ?string $type = null)
 * @method static QueryBuilder<static>|Content withAnyTagsOfAnyType($tags)
 * @method static QueryBuilder<static>|Content withAnyTagsOfType((array|string) $type)
 * @method static QueryBuilder<static>|Content withDepth(string $as = 'depth')
 * @method static QueryBuilder<static>|Content withAssets(bool $withDrafts = true)
 * @method static Builder<static>|Content withTrashed()
 * @method static QueryBuilder<static>|Content withWhereHasLanguage(int $language_id)
 * @method static QueryBuilder<static>|Content withoutCurrent()
 * @method static QueryBuilder<static>|Content withoutRoot()
 * @method static QueryBuilder<static>|Content withoutSelf()
 * @method static QueryBuilder<static>|Content withoutTags((ArrayAccess|\Spatie\Tags\Tag|array|string) $tags, ?string $type = null)
 * @method static Builder<static>|Content withoutTrashed()
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
    use HasUuids;
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
        'publish_from',
        'publish_to',
        'is_published',
        'order',
        'site_id',
        'type_id',
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
        return static::where('id', $value)->select($select)->first();
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
        return $this->belongsTo(Page::class, 'meta->page_id', 'id');
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

    protected function actions(): Attribute
    {
        return Attribute::make(get: fn () => $this->meta['actions'] ?? []);
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publish_from' => 'datetime',
            'publish_to' => 'datetime',
            'meta' => 'json',
        ];
    }
}
