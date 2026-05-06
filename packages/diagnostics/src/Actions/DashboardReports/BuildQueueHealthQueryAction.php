<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Actions\DashboardReports;

use Capell\Diagnostics\Models\FailedJob;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Action;

final class BuildQueueHealthQueryAction extends Action
{
    public function handle(): Builder
    {
        return FailedJob::query()->latest('failed_at');
    }
}
