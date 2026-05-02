<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoTools\Models\PageSeoSnapshot;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static PageSeoSnapshot run(Page $page, Site $site, Language $language)
 */
final class RefreshPageSeoSnapshotAction
{
    use AsAction;

    public function handle(Page $page, Site $site, Language $language): PageSeoSnapshot
    {
        $page->loadMissing([
            'creator',
            'type',
        ]);

        $report = BuildPageSeoReportAction::run($page, $site, $language);

        return PersistPageSeoSnapshotAction::run($page, $site, $language, $report);
    }
}
