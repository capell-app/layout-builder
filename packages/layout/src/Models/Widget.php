<?php

declare(strict_types=1);

namespace Capell\Layout\Models;

use Bkwld\Cloner\Cloneable;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Enums\PublishStatusEnum;
use Capell\Core\Models\Concerns\HasMetaData;
use Capell\Core\Models\Concerns\HasPageCache;
use Capell\Core\Models\Concerns\HasPublishDates;
use Capell\Core\Models\Concerns\HasStatus;
use Capell\Core\Models\Concerns\HasTranslations;
use Capell\Core\Models\Contracts\Statusable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Capell\Layout\Database\Factories\WidgetFactory;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Observers\WidgetObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Staudenmeir\EloquentJsonRelations\Relations\HasManyJson;
use Wildside\Userstamps\Userstamps;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WidgetAsset> $assets
 * @property-read int|null $assets_count
 * @property-read \Kalnoy\Nestedset\Collection<int, Content> $contents
 * @property-read int|null $contents_count
 * @property-read User|null $creator
 * @property-read User|null $destroyer
 * @property-read User|null $editor
 * @property-read PublishStatusEnum $publish_status
 * @property-read Media|null $image
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Language> $languages
 * @property-read int|null $languages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read \Kalnoy\Nestedset\Collection<int, Page> $pages
 * @property-read int|null $pages_count
 * @property-read Translation|null $translation
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Translation> $translations
 * @property-read int|null $translations_count
 * @property-read Type|null $type
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WidgetAsset> $widgetAssets
 * @property-read int|null $widget_assets_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Layout[] $layouts
 * @property-read int|null $layouts_count
 *
 * @method static Builder<static>|Widget disabled()
 * @method static Builder<static>|Widget enabled()
 * @method static Builder<static>|Widget expired(Model $model)
 * @method static WidgetFactory factory($count = null, $state = [])
 * @method static Builder<static>|Widget newModelQuery()
 * @method static Builder<static>|Widget newQuery()
 * @method static Builder<static>|Widget onlyTrashed()
 * @method static Builder<static>|Widget ordered(string $dir = 'asc')
 * @method static Builder<static>|Widget pending(Model $model)
 * @method static Builder<static>|Widget published(Model $model)
 * @method static Builder<static>|Widget query()
 * @method static Builder<static>|Widget status(bool $enabled)
 * @method static Builder<static>|Widget withLayoutsCount()
 * @method static Builder<static>|Widget withTrashed()
 * @method static Builder<static>|Widget withWhereHasLanguage(int $language_id)
 * @method static Builder<static>|Widget withoutTrashed()
 *
 * @property-read Media|null $backgroundImage
 *
 * @mixin \Eloquent
 */
#[ObservedBy(WidgetObserver::class)]
class Widget extends Model implements PageCacheable, Statusable
{
    use Cloneable;

    /** @use HasFactory<WidgetFactory> */
    use HasFactory;

    use HasJsonRelationships;
    use HasMetaData;
    use HasPageCache;
    use HasPublishDates;
    use HasRelationships;
    use HasStatus;
    use HasTranslations;
    use SoftDeletes;
    use Userstamps;

    public const COMPONENT_SLOT = 'slot';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'admin',
        'key',
        'meta',
        'name',
        'publish_from',
        'publish_to',
        'status',
        'type_id',
    ];

    /**
     * Relations on this model that should be cloned
     *
     * @var array|string[]
     */
    protected array $cloneable_relations = [
        'translations',
        'assets',
    ];

    protected static string $factory = WidgetFactory::class;

    public static function getOptions(string $key = 'id', string $value = 'name'): Collection
    {
        return self::query()->enabled()->ordered()->pluck($value, $key);
    }

    public function getComponent(): ?string
    {
        return $this->getMetaComponent()
            ?? config('capell-layout.default_widget', 'capell-layout::widget.default');
    }

    public function getMetaComponent(): ?string
    {
        return $this->meta['component'] ?? $this->type->meta['component'] ?? null;
    }

    public function getMetaComponentType(): string
    {
        $metaComponent = $this->getMetaComponent();

        if ($metaComponent && str_contains((string) $this->getMetaComponent(), 'livewire')) {
            return 'livewire';
        }

        return 'blade';
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'meta->image_id');
    }

    public function backgroundImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'meta->background_image_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class)->where('type', LayoutTypeEnum::Widget);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(WidgetAsset::class);
    }

    public function widgetAssets(): HasMany
    {
        return $this->assets()
            ->whereNull('page_id');
    }

    public function pageAssets(Page $page, string $container, int $occurrence): HasMany
    {
        return $this->assets()
            ->where('widget_assets.page_id', $page->id)
            ->where('widget_assets.container', $container)
            ->where('widget_assets.occurrence', $occurrence);
    }

    public function pages(): MorphToMany
    {
        return $this->morphedByMany(
            Page::class,
            'asset',
            'widget_assets',
            'widget_id',
            'asset_id',
        );
    }

    public function media(): MorphToMany
    {
        return $this->morphedByMany(
            Media::class,
            'asset',
            'widget_assets',
            'widget_id',
            'asset_id',
        );
    }

    public function contents(): MorphToMany
    {
        return $this->morphedByMany(
            Content::class,
            'asset',
            'widget_assets',
            'widget_id',
            'asset_id',
        );
    }

    public function layouts(): HasManyJson
    {
        return $this->hasManyJson(Layout::class, 'widgets', 'key');
    }

    public function scopeOrdered(Builder $query, string $dir = 'asc'): void
    {
        $query->orderBy($this->qualifyColumn('order'), $dir)
            ->orderBy($this->qualifyColumn('name'), $dir);
    }

    public function scopeWithLayoutsCount(Builder $query): void
    {
        $query->addRawSelect(
            match (DB::getDriverName()) {
                'sqlite' => <<<'SQL'
                    (SELECT COUNT(*) FROM layouts WHERE EXISTS (SELECT 1 FROM json_each(layouts.widgets) WHERE value = widgets.key))
                SQL,
                default => <<<'SQL'
                    (SELECT COUNT(*) FROM layouts WHERE JSON_CONTAINS(layouts.widgets, JSON_QUOTE(widgets.key)))
                SQL,
            }.' AS layouts_count'
        );
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'admin' => 'json',
            'meta' => 'json',
            'publish_from' => 'datetime',
            'publish_to' => 'datetime',
            'status' => 'boolean',
        ];
    }
}
