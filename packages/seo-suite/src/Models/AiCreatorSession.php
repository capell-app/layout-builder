<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $status
 */
class AiCreatorSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'user_id',
        'status',
        'stage',
        'intent',
        'clarifications',
        'layout_proposal',
        'generated_output',
        'ai_messages',
        'ai_history_id',
        'workspace_id',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'user_id' => 'integer',
        'stage' => 'integer',
        'clarifications' => 'array',
        'layout_proposal' => 'array',
        'generated_output' => 'array',
        'ai_messages' => 'array',
    ];

    public function history(): BelongsTo
    {
        return $this->belongsTo(AIGenerationHistory::class, 'ai_history_id');
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isInProgress(): bool
    {
        return in_array($this->status, ['in_progress', 'generating', 'review'], true);
    }
}
