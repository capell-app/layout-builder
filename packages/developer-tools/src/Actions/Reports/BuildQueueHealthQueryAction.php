<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Actions\Reports;

use Capell\DeveloperTools\Models\FailedJob;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Action;

final class BuildQueueHealthQueryAction extends Action
{
    public function handle(): Builder
    {
        return FailedJob::query()->latest('failed_at');
    }
}
