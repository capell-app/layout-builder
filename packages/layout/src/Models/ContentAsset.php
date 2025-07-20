<?php

declare(strict_types=1);

namespace Capell\Layout\Models;

use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Models\Concerns\HasAssets;
use Capell\Core\Models\Concerns\HasPageCache;
use Capell\Layout\Database\Factories\ContentAssetFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User;
use Wildside\Userstamps\Userstamps;

/**
 * @property-read Model|Eloquent $asset
 * @property-read Content|null $content
 * @property-read User|null $creator
 * @property-read User|null $destroyer
 * @property-read User|null $editor
 * @property-read string $asset_key
 *
 * @method static ContentAssetFactory factory($count = null, $state = [])
 * @method static Builder<static>|ContentAsset newModelQuery()
 * @method static Builder<static>|ContentAsset newQuery()
 * @method static Builder<static>|ContentAsset query()
 * @method static Builder<static>|ContentAsset withAssets(bool $withDrafts = true)
 *
 * @mixin Eloquent
 */
class ContentAsset extends Model implements PageCacheable
{
    use HasAssets;

    /** @use HasFactory<ContentAssetFactory> */
    use HasFactory;

    use HasPageCache;
    use Userstamps;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'content_id',
        'order',
        'asset_id',
        'asset_type',
    ];

    protected static string $factory = ContentAssetFactory::class;

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function asset(): MorphTo
    {
        return $this->morphTo('asset', 'asset_type', 'asset_id', 'id');
    }

    protected function assetKey(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->asset_type.'.'.$this->asset_id);
    }
}
