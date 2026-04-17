<?php

declare(strict_types=1);

namespace Capell\Mosaic\Models;

use Bkwld\Cloner\Cloneable;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Enums\PublishStatusEnum;
use Capell\Core\Models\Concerns\HasMetaData;
use Capell\Core\Models\Concerns\HasPublishDates;
use Capell\Core\Models\Concerns\HasStatus;
use Capell\Core\Models\Concerns\HasTranslations;
use Capell\Core\Models\Concerns\HasType;
use Capell\Core\Models\Concerns\HasUserstamps;
use Capell\Core\Models\Concerns\InteractsWithMedia;
use Capell\Core\Models\Contracts\Publishable;
use Capell\Core\Models\Contracts\Statusable;
use Capell\Core\Models\Contracts\Typeable;
use Capell\Core\Models\Contracts\Userstampable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Capell\Mosaic\Database\Factories\WidgetFactory;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use Capell\Mosaic\Observers\WidgetObserver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Staudenmeir\EloquentJsonRelations\Relations\HasManyJson;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WidgetAsset> $assets
 * @property-read int|null $assets_count
 * @property-read \Aimeos\Nestedset\Collection<int, Content> $contents
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
 * @property-read \Aimeos\Nestedset\Collection<int, Page> $pages
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WidgetAsset> $widgetPageAssets
 * @property-read int|null $widget_page_assets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Activity> $activities
 * @property-read int|null $activities_count
 * @property-read string|null $title
 *
 * @mixin Model
 *
 * @property int $id
 * @property string $name
 * @property int $type_id
 * @property string $key
 * @property CarbonImmutable|null $visible_from
 * @property CarbonImmutable|null $visible_until
 * @property string|null $content
 * @property array<array-key, mixed>|null $meta
 * @property array<array-key, mixed>|null $admin
 * @property int $order
 * @property bool $status
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 *
 * @method static Builder<static>|Widget whereAdmin($value)
 * @method static Builder<static>|Widget whereContent($value)
 * @method static Builder<static>|Widget whereCreatedAt($value)
 * @method static Builder<static>|Widget whereCreatedBy($value)
 * @method static Builder<static>|Widget whereDeletedAt($value)
 * @method static Builder<static>|Widget whereDeletedBy($value)
 * @method static Builder<static>|Widget whereId($value)
 * @method static Builder<static>|Widget whereKey($value)
 * @method static Builder<static>|Widget whereMeta($value)
 * @method static Builder<static>|Widget whereName($value)
 * @method static Builder<static>|Widget whereOrder($value)
 * @method static Builder<static>|Widget whereVisibleFrom($value)
 * @method static Builder<static>|Widget wherePublishTo($value)
 * @method static Builder<static>|Widget whereStatus($value)
 * @method static Builder<static>|Widget whereTypeId($value)
 * @method static Builder<static>|Widget whereUpdatedAt($value)
 * @method static Builder<static>|Widget whereUpdatedBy($value)
 *
 * @mixin Model
 */
#[ObservedBy(WidgetObserver::class)]
class Widget extends Model implements HasMedia, PageCacheable, Publishable, Statusable, Typeable, Userstampable
{
    use Cloneable;

    /** @use HasFactory<WidgetFactory> */
    use HasFactory;

    use HasJsonRelationships;
    use HasMetaData;
    use HasPublishDates;
    use HasRelationships;
    use HasStatus;
    use HasTranslations;
    use HasType;
    use HasUserstamps;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

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
        'visible_from',
        'visible_until',
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

    public static function getTypeGroups(): Collection
    {
        return Type::query()
            ->select('group')
            ->orderByRaw(
                'CASE `group`
                    WHEN "default" THEN 1
                    ELSE 0
                END DESC',
            )
            ->orderByRaw(
                'CASE `group`
                    WHEN "system" THEN 1
                    ELSE 0
                END ASC',
            )
            ->where('type', LayoutTypeEnum::Widget)
            ->whereNotNull('group')
            ->orderBy('group', 'asc')
            ->groupBy('group')
            ->pluck('group');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('widget')
            ->logAll()
            ->logExcept(['updated_at', 'created_at', 'deleted_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::Image->value)->singleFile();
        $this->addMediaCollection(MediaCollectionEnum::BackgroundImage->value)->singleFile();
    }

    public function getComponent(): ?string
    {
        return $this->getMetaComponent()
            ?? config('capell-mosaic.default_widget', 'capell-mosaic::widget.default');
    }

    public function getMetaComponent(): ?string
    {
        $value = $this->meta['component'] ?? $this->type->meta['component'] ?? null;

        return $value === null ? null : (string) $value;
    }

    public function getMetaComponentType(): string
    {
        if (isset($this->meta['livewire']) && $this->meta['livewire'] === true) {
            return 'livewire';
        }

        if (isset($this->type->meta['livewire']) && $this->type->meta['livewire'] === true) {
            return 'livewire';
        }

        return 'blade';
    }

    public function image(): MorphOne
    {
        return $this->morphOneMedia(MediaCollectionEnum::Image->value);
    }

    public function backgroundImage(): MorphOne
    {
        return $this->morphOneMedia(MediaCollectionEnum::BackgroundImage->value);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(WidgetAsset::class)
            ->chaperone();
    }

    public function widgetAssets(): HasMany
    {
        return $this->assets()
            ->whereNull('pageable_type')
            ->whereNull('pageable_id');
    }

    public function widgetPageAssets(): HasMany
    {
        return $this->assets()
            ->whereNotNull('pageable_type')
            ->whereNotNull('pageable_id');
    }

    public function pageAssets(Pageable $page, string $container, int $occurrence): HasMany
    {
        return $this->assets()
            ->where('widget_assets.pageable_type', $page->getMorphClass())
            ->where('widget_assets.pageable_id', $page->getKey())
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

    public function contents(): MorphToMany
    {
        return $this->morphedByMany(
            Collection::class,
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

    protected function scopeOrdered(Builder $query, string $dir = 'asc'): void
    {
        $query->orderBy($this->qualifyColumn('order'), $dir)
            ->orderBy($this->qualifyColumn('name'), $dir);
    }

    protected function scopeWithLayoutsCount(Builder $query): void
    {
        $query->addSelect(DB::raw(
            match (DB::getDriverName()) {
                'sqlite' => <<<'SQL'
                    (SELECT COUNT(*) FROM layouts WHERE EXISTS (SELECT 1 FROM json_each(layouts.widgets) WHERE value = widgets.key))
                SQL,
                default => <<<'SQL'
                    (SELECT COUNT(*) FROM layouts WHERE JSON_CONTAINS(layouts.widgets, JSON_QUOTE(widgets.key)))
                SQL,
            } . ' AS layouts_count',
        ));
    }

    protected function casts(): array
    {
        return [
            'admin' => 'json',
            'meta' => 'json',
            'visible_from' => 'datetime',
            'visible_until' => 'datetime',
            'status' => 'boolean',
        ];
    }
}
