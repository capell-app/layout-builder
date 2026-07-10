<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Models;

use Capell\Core\Models\Concerns\HasUserstamps;
use Capell\Core\Models\Contracts\Userstampable;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Database\Factories\LayoutPresetFactory;
use Capell\LayoutBuilder\Enums\LayoutPresetMode;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property int $id
 * @property int $site_id
 * @property string|null $theme_key
 * @property string $name
 * @property string $key
 * @property string $category
 * @property string $scope
 * @property LayoutPresetMode $mode
 * @property int $snapshot_version
 * @property int $revision
 * @property array<int, string>|null $tags
 * @property string|null $description
 * @property array<array-key, mixed> $snapshot
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
final class LayoutPreset extends Model implements Userstampable
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    use HasUserstamps;

    protected $table = 'layout_presets';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'site_id',
        'theme_key',
        'name',
        'key',
        'category',
        'scope',
        'mode',
        'snapshot_version',
        'revision',
        'tags',
        'description',
        'snapshot',
    ];

    protected static string $factory = LayoutPresetFactory::class;

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return HasMany<LayoutPresetUsage, $this> */
    public function usages(): HasMany
    {
        return $this->hasMany(LayoutPresetUsage::class, 'preset_id');
    }

    /** @return HasMany<LayoutPresetSyncRun, $this> */
    public function syncRuns(): HasMany
    {
        return $this->hasMany(LayoutPresetSyncRun::class, 'preset_id');
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function scopeForSite(Builder $query, Site|int $site): void
    {
        $query->where('site_id', $site instanceof Site ? $site->getKey() : $site);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'snapshot' => 'json',
            'mode' => LayoutPresetMode::class,
            'snapshot_version' => 'integer',
            'revision' => 'integer',
            'tags' => 'array',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
