<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions\DashboardReports;

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Action;
use Spatie\Activitylog\Models\Activity;

final class BuildActivityTrailQueryAction extends Action
{
    public function handle(): Builder
    {
        return Activity::query()
            ->whereIn('subject_type', [
                (new Page)->getMorphClass(),
                (new Site)->getMorphClass(),
                (new Workspace)->getMorphClass(),
            ])
            ->whereIn('event', ['created', 'updated', 'deleted'])
            ->where('created_at', '>=', now()->subDays(30))->latest();
    }
}
