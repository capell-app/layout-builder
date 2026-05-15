<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Models;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Blueprint as CoreBlueprint;
use Capell\Core\Models\Layout as CoreLayout;
use Capell\Core\Models\LayoutModule as CoreElement;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Database\Factories\ElementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use Staudenmeir\EloquentJsonRelations\Relations\HasManyJson;

class Element extends CoreElement
{
    protected $table = 'elements';

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

    protected static string $factory = ElementFactory::class;

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(CoreBlueprint::class, 'blueprint_id');
    }

    public function type(): BelongsTo
    {
        return $this->blueprint();
    }

    public function assets(): HasMany
    {
        return $this->hasMany(ElementAsset::class, 'layout_element_id')
            ->chaperone();
    }

    public function layoutElementAssets(): HasMany
    {
        return $this->assets()
            ->whereNull('pageable_type')
            ->whereNull('pageable_id');
    }

    public function elementAssets(): HasMany
    {
        return $this->layoutElementAssets();
    }

    public function layoutModuleAssets(): HasMany
    {
        return $this->layoutElementAssets();
    }

    public function elementPageAssets(): HasMany
    {
        return $this->assets()
            ->whereNotNull('pageable_type')
            ->whereNotNull('pageable_id');
    }

    public function layoutModulePageAssets(): HasMany
    {
        return $this->elementPageAssets();
    }

    public function pageAssets(Pageable $page, string $container, int $occurrence): HasMany
    {
        return $this->assets()
            ->where('layout_element_assets.pageable_type', $page->getMorphClass())
            ->where('layout_element_assets.pageable_id', $page->getKey())
            ->where('layout_element_assets.container', $container)
            ->where('layout_element_assets.occurrence', $occurrence);
    }

    public function pages(): MorphToMany
    {
        return $this->morphedByMany(
            Page::class,
            'asset',
            'layout_element_assets',
            'layout_element_id',
            'asset_id',
        );
    }

    public function layouts(): HasManyJson
    {
        return $this->hasManyJson(CoreLayout::class, 'elements', 'key');
    }

    protected function scopeWithLayoutsCount(Builder $query): void
    {
        $query->addSelect(DB::raw(
            match (DB::getDriverName()) {
                'sqlite' => <<<'SQL'
                    (SELECT COUNT(*) FROM layouts WHERE EXISTS (SELECT 1 FROM json_each(layouts.elements) WHERE value = elements.key))
                SQL,
                default => <<<'SQL'
                    (SELECT COUNT(*) FROM layouts WHERE JSON_CONTAINS(layouts.elements, JSON_QUOTE(elements.key)))
                SQL,
            } . ' AS layouts_count',
        ));
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
}
