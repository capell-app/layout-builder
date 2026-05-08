<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $render_profile_id
 * @property string $status
 * @property string|null $message
 */
class FrontendOptimizationRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'finished_at',
        'message',
        'render_profile_id',
        'started_at',
        'status',
    ];

    protected $casts = [
        'finished_at' => 'datetime',
        'started_at' => 'datetime',
    ];

    public function renderProfile(): BelongsTo
    {
        return $this->belongsTo(FrontendRenderProfile::class, 'render_profile_id');
    }
}
