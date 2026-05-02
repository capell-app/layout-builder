<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions\Reports;

use Capell\Admin\Support\SiteScope;
use Capell\SeoTools\Models\BrokenLink;
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
