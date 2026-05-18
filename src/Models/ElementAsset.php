<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Models;

use Capell\Core\Concerns\HasCapellMedia;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Concerns\ComposhipsJsonRelationshipsTrait;
use Capell\Core\Models\Concerns\HasAssets;
use Capell\Core\Models\Concerns\HasMetaData;
use Capell\Core\Models\Concerns\HasUserstamps;
use Capell\Core\Models\Contracts\Userstampable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Database\Factories\ElementAssetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int|null $layout_element_id
 */
class ElementAsset extends Model implements HasMedia, Userstampable
{
    use ComposhipsJsonRelationshipsTrait;
    use HasAssets;
    use HasCapellMedia;

    /** @use HasFactory<ElementAssetFactory> */
    use HasFactory;

    use HasMetaData;
    use HasUserstamps;

    protected $table = 'layout_element_assets';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'container',
        'workspace_id',
        'pageable_type',
        'pageable_id',
        'meta',
        'occurrence',
        'order',
        'asset_id',
        'asset_type',
        'layout_element_id',
    ];

    protected static string $factory = ElementAssetFactory::class;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::Image->value)->singleFile();
    }

    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'layout_element_id');
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

    protected function getElementIdAttribute(): ?int
    {
        $value = $this->attributes['layout_element_id'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    protected function setElementIdAttribute(mixed $value): void
    {
        $this->attributes['layout_element_id'] = $value;
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

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'meta' => 'json',
            'workspace_id' => 'integer',
            'order' => 'integer',
            'occurrence' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
