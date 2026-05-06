<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions\DashboardReports;

use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildStaleDraftsQueryAction
{
    use AsAction;

    public function handle(int $thresholdDays = 14): Builder
    {
        $cutoff = now()->subDays($thresholdDays);

        return Workspace::query()
            ->whereIn('status', [
                WorkspaceStatusEnum::Open->value,
                WorkspaceStatusEnum::InReview->value,
            ])
            ->where('updated_at', '<', $cutoff);
    }
}
