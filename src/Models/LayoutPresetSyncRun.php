<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Models;

use Capell\LayoutBuilder\Enums\LayoutPresetSyncRunStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

class LayoutPresetSyncRun extends Model
{
    use HasUuids;

    protected $table = 'layout_preset_sync_runs';

    protected $fillable = [
        'uuid',
        'preset_id',
        'revision',
        'initiated_by',
        'excluded_usage_id',
        'status',
        'summary',
        'queued_at',
        'started_at',
        'completed_at',
    ];

    /** @return list<string> */
    #[Override]
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** @return BelongsTo<LayoutPreset, $this> */
    public function preset(): BelongsTo
    {
        return $this->belongsTo(LayoutPreset::class, 'preset_id');
    }

    /** @return BelongsTo<LayoutPresetUsage, $this> */
    public function excludedUsage(): BelongsTo
    {
        return $this->belongsTo(LayoutPresetUsage::class, 'excluded_usage_id');
    }

    /** @return HasMany<LayoutPresetSyncResult, $this> */
    public function results(): HasMany
    {
        return $this->hasMany(LayoutPresetSyncResult::class, 'run_id');
    }

    /** @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return [
            'status' => LayoutPresetSyncRunStatus::class,
            'summary' => 'array',
            'queued_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
