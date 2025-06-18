<?php

declare(strict_types=1);

namespace Capell\Layout\Models;

use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Models\Concerns\HasAssets;
use Capell\Core\Models\Concerns\HasMetaData;
use Capell\Core\Models\Concerns\HasPageCache;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Layout\Database\Factories\WidgetAssetFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;
use Wildside\Userstamps\Userstamps;

/**
 * @property-read Model|Eloquent $asset
 * @property-read \Illuminate\Foundation\Auth\User|null $creator
 * @property-read \Illuminate\Foundation\Auth\User|null $destroyer
 * @property-read \Illuminate\Foundation\Auth\User|null $editor
 * @property-read string $asset_key
 * @property-read Media|null $image
 * @property-read Page|null $page
 * @property-read Page|null $relatedPage
 * @property-read Widget|null $widget
 * @property-read \Illuminate\Database\Eloquent\Collection|Content[] $related
 * @property-read int|null $related_count
 *
 * @method static \Capell\Layout\Database\Factories\WidgetAssetFactory factory($count = null, $state = [])
 * @method static Builder<static>|WidgetAsset newModelQuery()
 * @method static Builder<static>|WidgetAsset newQuery()
 * @method static Builder<static>|WidgetAsset ordered(string $dir = 'asc')
 * @method static Builder<static>|WidgetAsset query()
 * @method static Builder<static>|WidgetAsset withAssets(bool $withDrafts = true)
 *
 * @mixin \Eloquent
 * @mixin Eloquent
 */
class WidgetAsset extends Model implements PageCacheable
{
    use HasAssets;

    /** @use HasFactory<WidgetAssetFactory> */
    use HasFactory;

    use HasJsonRelationships;
    use HasMetaData;
    use HasPageCache;
    use Userstamps;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'container',
        'page_id',
        'meta',
        'occurrence',
        'order',
        'asset_id',
        'asset_type',
        'widget_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'json',
        'order' => 'integer',
        'occurrence' => 'integer',
    ];

    protected static string $factory = WidgetAssetFactory::class;

    public static function getTypes(): array
    {
        return TypeEnum::getResourceTypes();
    }

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function asset(): MorphTo
    {
        return $this->morphTo('asset', 'asset_type', 'asset_id', 'uuid');
    }

    public function related(): BelongsToJson
    {
        return $this->belongsToJson(Content::class, 'meta->related');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'meta->image_id');
    }

    public function relatedPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'meta->related_page_id');
    }

    public function scopeOrdered(Builder $query, string $dir = 'asc'): void
    {
        $query->orderBy($this->qualifyColumn('order'), $dir);
    }

    public function getAssetKeyAttribute(): string
    {
        return $this->asset_type.'.'.$this->asset_id;
    }
}
