<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions\Reports;

use Capell\Admin\Support\SiteScope;
use Capell\Core\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildTranslationCoverageQueryAction
{
    use AsAction;

    public function handle(): Builder
    {
        $query = Page::query()
            ->with(['site.languages', 'translations']);

        return SiteScope::applyForCurrentActor($query);
    }
}
