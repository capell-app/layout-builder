<?php

declare(strict_types=1);

namespace Capell\Layout\Models;

use Capell\Admin\Concerns\HasResources;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Models\Concerns\HasPageCache;
use Capell\Layout\Database\Factories\ContentAssetFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Wildside\Userstamps\Userstamps;

/**
 * @property-read Model|Eloquent $asset
 * @property-read Content|null $content
 * @property-read \Illuminate\Foundation\Auth\User|null $creator
 * @property-read \Illuminate\Foundation\Auth\User|null $destroyer
 * @property-read \Illuminate\Foundation\Auth\User|null $editor
 * @property-read string $asset_key
 *
 * @method static \Capell\Layout\Database\Factories\ContentAssetFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAsset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAsset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAsset query()
 *
 * @mixin \Eloquent
 * @mixin Eloquent
 */
class ContentAsset extends Model implements PageCacheable
{
    /** @use HasFactory<ContentAssetFactory> */
    use HasFactory;

    use HasPageCache;
    use HasResources;
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

    public static function getTypes(): array
    {
        return TypeEnum::getResourceTypes();
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function asset(): MorphTo
    {
        return $this->morphTo('asset', 'asset_type', 'asset_id', 'uuid');
    }

    public function getAssetKeyAttribute(): string
    {
        return $this->asset_type.'.'.$this->asset_id;
    }
}
