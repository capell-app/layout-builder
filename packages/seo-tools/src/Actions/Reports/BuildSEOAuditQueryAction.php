<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions\Reports;

use Capell\Admin\Support\SiteScope;
use Capell\Core\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildSEOAuditQueryAction
{
    use AsAction;

    public function handle(): Builder
    {
        $query = Page::query()
            ->with([
                'pageUrl.siteDomain',
                'site.language',
                'translation.language',
                'translations.language',
            ]);

        return SiteScope::applyForCurrentActor($query);
    }
}
