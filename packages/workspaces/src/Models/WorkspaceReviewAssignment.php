<?php

declare(strict_types=1);

namespace Capell\Workspaces\Models;

use Capell\Workspaces\Database\Factories\WorkspaceReviewAssignmentFactory;
use Capell\Workspaces\Enums\ReviewDecisionEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Assignment for a specific reviewer to decide on a workspace. Lives alongside
 * the existing {@see WorkspaceApproval} audit table: assignments express who
 * _must_ approve; approvals record what they did. Workspace transitions to
 * Approved once every assignment is decided=Approved.
 *
 * @property int $id
 * @property int $workspace_id
 * @property string|null $reviewer_type
 * @property int|null $reviewer_id
 * @property string $required_for
 * @property ReviewDecisionEnum|null $decision
 * @property string|null $notes
 * @property CarbonImmutable|null $decided_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Workspace $workspace
 */
class WorkspaceReviewAssignment extends Model
{
    /** @use HasFactory<WorkspaceReviewAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'reviewer_type',
        'reviewer_id',
        'required_for',
        'decision',
        'notes',
        'decided_at',
    ];

    protected static string $factory = WorkspaceReviewAssignmentFactory::class;

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function reviewer(): MorphTo
    {
        return $this->morphTo();
    }

    public function isDecided(): bool
    {
        return $this->decision !== null;
    }

    public function isApproved(): bool
    {
        return $this->decision === ReviewDecisionEnum::Approved;
    }

    public function isRejected(): bool
    {
        return $this->decision === ReviewDecisionEnum::Rejected;
    }

    protected function casts(): array
    {
        return [
            'decision' => ReviewDecisionEnum::class,
            'decided_at' => 'immutable_datetime',
        ];
    }
}
