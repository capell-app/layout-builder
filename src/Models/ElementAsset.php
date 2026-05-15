<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Models;

use Capell\Core\Models\LayoutModuleAsset as CoreElementAsset;
use Capell\LayoutBuilder\Database\Factories\ElementAssetFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $layout_element_id
 */
class ElementAsset extends CoreElementAsset
{
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

    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'layout_element_id');
    }

    public function widget(): BelongsTo
    {
        return $this->element();
    }

    public function layoutModule(): BelongsTo
    {
        return $this->element();
    }

    protected function getWidgetIdAttribute(): ?int
    {
        return $this->getElementIdAttribute();
    }

    protected function setWidgetIdAttribute(mixed $value): void
    {
        $this->setElementIdAttribute($value);
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

    protected function getLayoutModuleIdAttribute(): ?int
    {
        return $this->getElementIdAttribute();
    }

    protected function setLayoutModuleIdAttribute(mixed $value): void
    {
        $this->setElementIdAttribute($value);
    }
}
