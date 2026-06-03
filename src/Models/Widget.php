<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Models;

use Bkwld\Cloner\Cloneable;
use Capell\Core\Actions\ResolveRenderableComponentAction;
use Capell\Core\Concerns\HasCapellMedia;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Blueprint as CoreBlueprint;
use Capell\Core\Models\Concerns\HasBlueprint;
use Capell\Core\Models\Concerns\HasMetaData;
use Capell\Core\Models\Concerns\HasPublishDates;
use Capell\Core\Models\Concerns\HasStatus;
use Capell\Core\Models\Concerns\HasTranslations;
use Capell\Core\Models\Concerns\HasUserstamps;
use Capell\Core\Models\Contracts\Blueprintable;
use Capell\Core\Models\Contracts\Publishable;
use Capell\Core\Models\Contracts\Statusable;
use Capell\Core\Models\Contracts\Translatable;
use Capell\Core\Models\Contracts\Userstampable;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Database\Factories\WidgetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Override;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Widget extends Model implements Blueprintable, HasMedia, Publishable, Statusable, Translatable, Userstampable
{
    use Cloneable;
    use HasBlueprint;
    use HasCapellMedia;

    /** @use HasFactory<WidgetFactory> */
    use HasFactory;

    use HasMetaData;
    use HasPublishDates;
    use HasRelationships;

    /** @use HasStatus<self> */
    use HasStatus;

    use HasTranslations;
    use HasUserstamps;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'widgets';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'admin',
        'component',
        'component_item',
        'is_livewire',
        'key',
        'meta',
        'name',
        'visible_from',
        'visible_until',
        'status',
        'blueprint_id',
        'view_file',
    ];

    protected static string $factory = WidgetFactory::class;

    /**
     * @var array|string[]
     */
    protected array $cloneable_relations = [
        'translations',
        'assets',
    ];

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
        $this->addMediaCollection('hero_video_desktop')->singleFile();
        $this->addMediaCollection('hero_video_tablet')->singleFile();
        $this->addMediaCollection('hero_video_mobile')->singleFile();
        $this->addMediaCollection('hero_image_desktop')->singleFile();
        $this->addMediaCollection('hero_image_tablet')->singleFile();
        $this->addMediaCollection('hero_image_mobile')->singleFile();
    }

    /** @return BelongsTo<CoreBlueprint, $this> */
    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(CoreBlueprint::class, 'blueprint_id');
    }

    /** @return BelongsTo<CoreBlueprint, $this> */
    public function type(): BelongsTo
    {
        return $this->blueprint();
    }

    public function getMetaComponentType(): string
    {
        if ($this->is_livewire !== null) {
            return $this->is_livewire ? 'livewire' : 'blade';
        }

        if (array_key_exists('livewire', $this->meta ?? [])) {
            return $this->meta['livewire'] === true ? 'livewire' : 'blade';
        }

        $blueprint = $this->relationLoaded('blueprint')
            ? $this->getRelation('blueprint')
            : ($this->relationLoaded('type') ? $this->getRelation('type') : null);

        if ($blueprint instanceof CoreBlueprint && $blueprint->is_livewire !== null) {
            return $blueprint->is_livewire ? 'livewire' : 'blade';
        }

        if ($blueprint instanceof CoreBlueprint && array_key_exists('livewire', $blueprint->meta ?? [])) {
            return $blueprint->meta['livewire'] === true ? 'livewire' : 'blade';
        }

        return 'blade';
    }

    public function getComponent(): ?string
    {
        return ResolveRenderableComponentAction::run(
            'layout-widget',
            $this->getMetaComponent() ?? config('capell-layout-builder.default_widget', 'capell.widget.default'),
            $this->getMetaComponentType(),
        );
    }

    public function getMetaComponent(): ?string
    {
        $blueprint = $this->loadedBlueprint();
        $blueprintMeta = $blueprint instanceof CoreBlueprint ? ($blueprint->meta ?? []) : [];

        $value = $this->component
            ?? $this->meta['component']
            ?? ($blueprint instanceof CoreBlueprint ? $blueprint->component : null)
            ?? ($blueprintMeta['component'] ?? null)
            ?? null;

        return $value === null ? null : (string) $value;
    }

    public function getComponentItem(): ?string
    {
        $blueprint = $this->loadedBlueprint();
        $blueprintMeta = $blueprint instanceof CoreBlueprint ? ($blueprint->meta ?? []) : [];

        $value = $this->component_item
            ?? $this->meta['component_item']
            ?? ($blueprint instanceof CoreBlueprint ? $blueprint->component_item : null)
            ?? ($blueprintMeta['component_item'] ?? null)
            ?? null;

        return $value === null ? null : (string) $value;
    }

    public function getViewFile(): ?string
    {
        $blueprint = $this->loadedBlueprint();
        $blueprintMeta = $blueprint instanceof CoreBlueprint ? ($blueprint->meta ?? []) : [];

        $value = $this->view_file
            ?? $this->meta['view_file']
            ?? ($blueprint instanceof CoreBlueprint ? $blueprint->view_file : null)
            ?? ($blueprintMeta['view_file'] ?? null)
            ?? null;

        return $value === null ? null : (string) $value;
    }

    /** @return MorphOne<Media, $this> */
    public function image(): MorphOne
    {
        return $this->morphOneMedia(MediaCollectionEnum::Image->value);
    }

    /** @return MorphOne<Media, $this> */
    public function backgroundImage(): MorphOne
    {
        return $this->morphOneMedia(MediaCollectionEnum::BackgroundImage->value);
    }

    /** @return HasMany<WidgetAsset, $this> */
    public function assets(): HasMany
    {
        return $this->hasMany(WidgetAsset::class, 'widget_id')
            ->chaperone();
    }

    /** @return HasMany<WidgetAsset, $this> */
    public function layoutWidgetAssets(): HasMany
    {
        return $this->assets()
            ->whereNull('pageable_type')
            ->whereNull('pageable_id');
    }

    /** @return HasMany<WidgetAsset, $this> */
    public function widgetAssets(): HasMany
    {
        return $this->layoutWidgetAssets();
    }

    /** @return HasMany<WidgetAsset, $this> */
    public function widgetPageAssets(): HasMany
    {
        return $this->assets()
            ->whereNotNull('pageable_type')
            ->whereNotNull('pageable_id');
    }

    /** @return HasMany<WidgetWidget, $this> */
    public function widgets(): HasMany
    {
        return $this->hasMany(WidgetWidget::class)
            ->ordered()
            ->chaperone();
    }

    /** @return HasMany<WidgetAsset, $this> */
    public function pageAssets(Pageable $page, string $container, int $occurrence): HasMany
    {
        return $this->assets()
            ->where('widget_assets.pageable_type', $page->getMorphClass())
            ->where('widget_assets.pageable_id', $page->getKey())
            ->where('widget_assets.container', $container)
            ->where('widget_assets.occurrence', $occurrence);
    }

    /**
     * @return MorphToMany<Page, $this>
     */
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

    /**
     * @param  Builder<Model>  $query
     */
    protected function scopeWithLayoutsCount(Builder $query): void
    {
        if ($query->getQuery()->columns === null) {
            $query->select($this->qualifyColumn('*'));
        }

        $query->addSelect(DB::raw(
            match (DB::getDriverName()) {
                'sqlite' => <<<'SQL'
                    (SELECT COUNT(*) FROM layouts WHERE EXISTS (
                        SELECT 1 FROM json_tree(layouts.containers)
                        WHERE json_tree.value = widgets.key
                    ))
                SQL,
                default => <<<'SQL'
                    (SELECT COUNT(*) FROM layouts WHERE JSON_SEARCH(layouts.containers, 'one', widgets.key) IS NOT NULL)
                SQL,
            } . ' AS layouts_count',
        ));
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function scopeOrdered(Builder $query, string $dir = 'asc'): void
    {
        $query->orderBy($this->qualifyColumn('order'), $dir)
            ->orderBy($this->qualifyColumn('name'), $dir);
    }

    protected function getTypeIdAttribute(): ?int
    {
        $value = $this->attributes['blueprint_id'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    protected function setTypeIdAttribute(mixed $value): void
    {
        $this->attributes['blueprint_id'] = $value;
    }

    protected function getBlueprintIdAttribute(): ?int
    {
        return $this->getTypeIdAttribute();
    }

    protected function setBlueprintIdAttribute(mixed $value): void
    {
        $this->attributes['blueprint_id'] = $value;
    }

    protected function setMetaAttribute(mixed $value): void
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            $this->attributes['meta'] = $value === null ? null : json_encode($value);

            return;
        }

        foreach (['component', 'component_item', 'view_file'] as $column) {
            if (array_key_exists($column, $value)) {
                $this->attributes[$column] = $this->nullableComponentString($value[$column]);
                unset($value[$column]);
            }
        }

        if (array_key_exists('livewire', $value)) {
            $this->attributes['is_livewire'] = (bool) $value['livewire'];
            unset($value['livewire']);
        }

        $this->attributes['meta'] = $value === [] ? null : json_encode($value);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'admin' => 'json',
            'is_livewire' => 'boolean',
            'meta' => 'json',
            'visible_from' => 'datetime',
            'visible_until' => 'datetime',
            'status' => 'boolean',
        ];
    }

    private function loadedBlueprint(): ?CoreBlueprint
    {
        $blueprint = $this->relationLoaded('blueprint')
            ? $this->getRelation('blueprint')
            : ($this->relationLoaded('type') ? $this->getRelation('type') : null);

        return $blueprint instanceof CoreBlueprint ? $blueprint : null;
    }

    private function nullableComponentString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
