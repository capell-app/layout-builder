<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static array{refreshed:int} run(Site $site, Language $language, int $chunkSize = 100)
 */
final class RefreshSiteSeoSnapshotsAction
{
    use AsAction;

    /**
     * @return array{refreshed:int}
     */
    public function handle(Site $site, Language $language, int $chunkSize = 100): array
    {
        $refreshed = 0;

        Page::query()
            ->where('site_id', $site->getKey())
            ->with([
                'creator',
                'site.language',
                'translation.language',
                'translations.language',
                'pageUrl.siteDomain',
                'type',
            ])
            ->orderBy('id')
            ->chunkById(
                $chunkSize,
                /** @param Collection<int, Page> $pages */
                function (Collection $pages) use ($site, $language, &$refreshed): void {
                    foreach ($pages as $page) {
                        RefreshPageSeoSnapshotAction::run($page, $site, $language);

                        $refreshed++;
                    }
                },
            );

        return ['refreshed' => $refreshed];
    }
}
