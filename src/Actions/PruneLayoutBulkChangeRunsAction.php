<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Enums\LayoutBulkChangeRunStatus;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Lorisleiva\Actions\Concerns\AsAction;

final class PruneLayoutBulkChangeRunsAction
{
    use AsAction;

    public function handle(?int $retentionDays = null): int
    {
        $retentionDays ??= (int) config('capell-layout-builder.bulk_change_retention_days', 90);

        return LayoutBulkChangeRun::query()
            ->whereIn('status', [
                LayoutBulkChangeRunStatus::Applied->value,
                LayoutBulkChangeRunStatus::PartiallyApplied->value,
                LayoutBulkChangeRunStatus::Reverted->value,
                LayoutBulkChangeRunStatus::PartiallyReverted->value,
                LayoutBulkChangeRunStatus::Failed->value,
                LayoutBulkChangeRunStatus::Blocked->value,
            ])
            ->where('updated_at', '<', now()->subDays(max(1, $retentionDays)))
            ->delete();
    }
}
