<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Models;

use Capell\LayoutBuilder\Enums\LayoutBulkChangeRunStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

class LayoutBulkChangeRun extends Model
{
    use HasUuids;

    protected $table = 'layout_bulk_change_runs';

    protected $fillable = [
        'uuid',
        'status',
        'criteria',
        'operation',
        'summary',
        'created_by',
        'approved_by',
        'applied_by',
        'approved_at',
        'applied_at',
    ];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** @return HasMany<LayoutBulkChangeResult, $this> */
    public function results(): HasMany
    {
        return $this->hasMany(LayoutBulkChangeResult::class, 'run_id');
    }

    /** @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return [
            'status' => LayoutBulkChangeRunStatus::class,
            'criteria' => 'array',
            'operation' => 'array',
            'summary' => 'array',
            'approved_at' => 'immutable_datetime',
            'applied_at' => 'immutable_datetime',
        ];
    }
}
