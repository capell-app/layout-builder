<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions\DashboardReports;

use Capell\Admin\Support\SiteScope;
use Capell\SeoSuite\Models\BrokenLink;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildBrokenLinksQueryAction
{
    use AsAction;

    public function handle(): Builder
    {
        return BrokenLink::query()
            ->where('http_status', '>=', 400)
            ->whereHas('page', fn (Builder $query): Builder => SiteScope::applyForCurrentActor($query))
            ->with('page');
    }
}
