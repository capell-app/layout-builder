<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum WorkspaceStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    /** Open for edits by collaborators. */
    case Open = 'open';

    /** Submitted for review — edits frozen, awaiting approvers. */
    case InReview = 'in_review';

    /** Fully approved — ready to publish. */
    case Approved = 'approved';

    /** Approved and queued for a future publish at `publish_at`. */
    case Scheduled = 'scheduled';

    /** Publish in progress. Exposed so the UI can disable actions during the transition. */
    case Publishing = 'publishing';

    /** Published into a live Version. Workspace is now immutable. */
    case Published = 'published';

    /** Discarded by its owner. Soft-deleted from admin lists. */
    case Abandoned = 'abandoned';

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'gray',
            self::InReview => 'warning',
            self::Approved => 'info',
            self::Scheduled => 'info',
            self::Publishing => 'info',
            self::Published => 'success',
            self::Abandoned => 'danger',
        };
    }

    public function getIcon(): string|Heroicon
    {
        return match ($this) {
            self::Open => Heroicon::OutlinedPencil,
            self::InReview => Heroicon::OutlinedClock,
            self::Approved => Heroicon::OutlinedCheckCircle,
            self::Scheduled => Heroicon::OutlinedCalendarDays,
            self::Publishing => Heroicon::OutlinedArrowPath,
            self::Published => Heroicon::OutlinedCheckBadge,
            self::Abandoned => Heroicon::OutlinedTrash,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => __('capell-admin::workspace.status.open'),
            self::InReview => __('capell-admin::workspace.status.in_review'),
            self::Approved => __('capell-admin::workspace.status.approved'),
            self::Scheduled => __('capell-admin::workspace.status.scheduled'),
            self::Publishing => __('capell-admin::workspace.status.publishing'),
            self::Published => __('capell-admin::workspace.status.published'),
            self::Abandoned => __('capell-admin::workspace.status.abandoned'),
        };
    }

    /** True when the workspace still accepts edits. */
    public function isEditable(): bool
    {
        return $this === self::Open;
    }

    /** True when the workspace is in a terminal (non-editable) state. */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Published, self::Abandoned], true);
    }

    /** True when the workspace is in the approval pipeline. */
    public function isInApprovalPipeline(): bool
    {
        return in_array($this, [self::InReview, self::Approved, self::Scheduled], true);
    }
}
