<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum WorkspaceApprovalActionEnum: string implements HasColor, HasLabel
{
    /** Submitter pushed the workspace into the review queue. */
    case Submitted = 'submitted';

    /** Reviewer approved at their level. */
    case Approved = 'approved';

    /** Reviewer rejected — workspace returns to `open` with notes. */
    case Rejected = 'rejected';

    /** Reviewer asked for changes — workspace returns to `open` with notes. */
    case ChangesRequested = 'changes_requested';

    public function getColor(): string
    {
        return match ($this) {
            self::Submitted => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::ChangesRequested => 'warning',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Submitted => __('capell-admin::workspace.approval_action.submitted'),
            self::Approved => __('capell-admin::workspace.approval_action.approved'),
            self::Rejected => __('capell-admin::workspace.approval_action.rejected'),
            self::ChangesRequested => __('capell-admin::workspace.approval_action.changes_requested'),
        };
    }
}
