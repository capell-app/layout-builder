<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions\DashboardReports;

use Capell\Core\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildScheduledPublishingQueryAction
{
    use AsAction;

    public function handle(): Builder
    {
        return Page::query()
            ->where(function (Builder $inner): void {
                $inner->where('visible_from', '>', now())
                    ->orWhere('visible_until', '>', now());
            });
    }
}
