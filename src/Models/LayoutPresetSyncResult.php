<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Models;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Enums\LayoutPresetSyncResultStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class LayoutPresetSyncResult extends Model
{
    protected $table = 'layout_preset_sync_results';

    protected $fillable = [
        'run_id',
        'usage_id',
        'layout_id',
        'container_key',
        'status',
        'reason',
        'details',
    ];

    /** @return BelongsTo<LayoutPresetSyncRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(LayoutPresetSyncRun::class, 'run_id');
    }

    /** @return BelongsTo<LayoutPresetUsage, $this> */
    public function usage(): BelongsTo
    {
        return $this->belongsTo(LayoutPresetUsage::class, 'usage_id');
    }

    /** @return BelongsTo<Layout, $this> */
    public function layout(): BelongsTo
    {
        return $this->belongsTo(Layout::class, 'layout_id');
    }

    /** @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return [
            'status' => LayoutPresetSyncResultStatus::class,
            'details' => 'array',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
