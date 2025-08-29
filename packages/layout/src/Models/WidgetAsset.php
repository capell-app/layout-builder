<?php

declare(strict_types=1);

namespace Capell\Layout\Models;

use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Models\Concerns\HasAssets;
use Capell\Core\Models\Concerns\HasMetaData;
use Capell\Core\Models\Concerns\HasPageCache;
use Capell\Core\Models\Concerns\InteractsWithMedia;
use Capell\Core\Models\Page;
use Capell\Layout\Database\Factories\WidgetAssetFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User;
use Spatie\MediaLibrary\HasMedia;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;
use Wildside\Userstamps\Userstamps;

/**
 * @property-read Model|Eloquent $asset
 * @property-read User|null $creator
 * @property-read User|null $destroyer
 * @property-read User|null $editor
 * @property-read string $asset_key
 * @property-read Media|null $image
 * @property-read Page|null $page
 * @property-read Page|null $relatedPage
 * @property-read Widget|null $widget
 * @property-read Collection|Content[] $related
 * @property-read int|null $related_count
 *
 * @method static WidgetAssetFactory factory($count = null, $state = [])
 * @method static Builder<static>|WidgetAsset newModelQuery()
 * @method static Builder<static>|WidgetAsset newQuery()
 * @method static Builder<static>|WidgetAsset ordered(string $dir = 'asc')
 * @method static Builder<static>|WidgetAsset query()
 * @method static Builder<static>|WidgetAsset withAssets(bool $withDrafts = true)
 *
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 *
 * @mixin Eloquent
 */
class WidgetAsset extends Model implements HasMedia, PageCacheable
{
    use HasAssets;

    /** @use HasFactory<WidgetAssetFactory> */
    use HasFactory;

    use HasJsonRelationships;
    use HasMetaData;
    use HasPageCache;
    use InteractsWithMedia;
    use Userstamps;

    public const MEDIA_IMAGE = 'image';

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

    protected static string $factory = WidgetAssetFactory::class;

    public static function totalWidgetPages(Widget $widget): int
    {
        return static::query()
            ->where('widget_id', $widget->id)
            ->whereNotNull('page_id')
            ->distinct('page_id')
            ->count('page_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(static::MEDIA_IMAGE)->singleFile();
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
        return $this->morphTo('asset', 'asset_type', 'asset_id', 'id');
    }

    public function related(): BelongsToJson
    {
        return $this->belongsToJson(Content::class, 'meta->related');
    }

    public function image(): MorphOne
    {
        return $this->morphOneMedia(static::MEDIA_IMAGE);
    }

    public function relatedPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'meta->related_page_id');
    }

    public function scopeOrdered(Builder $query, string $dir = 'asc'): void
    {
        $query->orderBy($this->qualifyColumn('order'), $dir);
    }

    protected function assetKey(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->asset_type . '.' . $this->asset_id);
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'json',
            'order' => 'integer',
            'occurrence' => 'integer',
        ];
    }
}
