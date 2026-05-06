<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Models;

use Capell\PublishingStudio\Database\Factories\WorkspaceApprovalFactory;
use Capell\PublishingStudio\Enums\WorkspaceApprovalActionEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Audit row for a single action taken against a workspace in the approval
 * pipeline (submit, approve, reject). Many approvals per workspace — the
 * chain of records IS the approval history.
 *
 * @property int $id
 * @property int $workspace_id
 * @property string|null $actionable_type
 * @property int|null $actionable_id
 * @property int $level
 * @property WorkspaceApprovalActionEnum $action
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Workspace $workspace
 */
class WorkspaceApproval extends Model
{
    /** @use HasFactory<WorkspaceApprovalFactory> */
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'actionable_type',
        'actionable_id',
        'level',
        'action',
        'notes',
    ];

    protected static string $factory = WorkspaceApprovalFactory::class;

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function actionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isApproval(): bool
    {
        return $this->action === WorkspaceApprovalActionEnum::Approved;
    }

    public function isRejection(): bool
    {
        return $this->action === WorkspaceApprovalActionEnum::Rejected;
    }

    public function isSubmission(): bool
    {
        return $this->action === WorkspaceApprovalActionEnum::Submitted;
    }

    public function isChangesRequested(): bool
    {
        return $this->action === WorkspaceApprovalActionEnum::ChangesRequested;
    }

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'action' => WorkspaceApprovalActionEnum::class,
        ];
    }
}
