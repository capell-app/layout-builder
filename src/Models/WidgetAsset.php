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
use Capell\LayoutBuilder\Database\Factories\WidgetAssetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int|null $widget_id
 */
class WidgetAsset extends Model implements HasMedia, Userstampable
{
    use ComposhipsJsonRelationshipsTrait;
    use HasAssets;
    use HasCapellMedia;

    /** @use HasFactory<WidgetAssetFactory> */
    use HasFactory;

    use HasMetaData;
    use HasUserstamps;

    protected $table = 'widget_assets';

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
        'widget_id',
    ];

    protected static string $factory = WidgetAssetFactory::class;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::Image->value)->singleFile();
    }

    /**
     * @return BelongsTo<Widget, $this>
     */
    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class, 'widget_id');
    }

    /**
     * @return BelongsTo<Widget, $this>
     */
    public function block(): BelongsTo
    {
        return $this->widget();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function pageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function asset(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function linkedPage(): MorphTo
    {
        return $this->morphTo('meta->linked_pageable_type', 'meta->linked_pageable_id');
    }

    protected function getBlockIdAttribute(): ?int
    {
        return $this->getWidgetIdAttribute();
    }

    protected function setBlockIdAttribute(mixed $value): void
    {
        $this->setWidgetIdAttribute($value);
    }

    protected function getWidgetIdAttribute(): ?int
    {
        $value = $this->attributes['widget_id'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    protected function setWidgetIdAttribute(mixed $value): void
    {
        $this->attributes['widget_id'] = $value;
    }

    protected function getAssetKeyAttribute(): string
    {
        return $this->asset_type . '.' . $this->asset_id;
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function scopeOrdered(Builder $query, string $dir = 'asc'): void
    {
        $query->orderBy($this->qualifyColumn('order'), $dir);
    }

    /**
     * @param  Builder<Model>  $query
     */
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
