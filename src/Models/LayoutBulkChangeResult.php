<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Models;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Enums\LayoutBulkChangeResultStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property LayoutBulkChangeResultStatus $status
 * @property int|null $layout_id
 * @property array<string, mixed>|null $original_containers
 * @property array<string, mixed>|null $proposed_containers
 * @property array<string, mixed>|null $changes
 * @property array<string, mixed>|null $warnings
 */
class LayoutBulkChangeResult extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    protected $table = 'layout_bulk_change_results';

    protected $fillable = [
        'run_id',
        'layout_id',
        'page_count',
        'status',
        'original_container_hash',
        'proposed_container_hash',
        'original_containers',
        'proposed_containers',
        'changes',
        'warnings',
        'skipped_reason',
        'applied_at',
        'reverted_at',
    ];

    /** @return BelongsTo<LayoutBulkChangeRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(LayoutBulkChangeRun::class, 'run_id');
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
            'status' => LayoutBulkChangeResultStatus::class,
            'page_count' => 'integer',
            'original_containers' => 'array',
            'proposed_containers' => 'array',
            'changes' => 'array',
            'warnings' => 'array',
            'applied_at' => 'immutable_datetime',
            'reverted_at' => 'immutable_datetime',
        ];
    }
}
