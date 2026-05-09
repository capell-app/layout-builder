<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Models\AiDiscoveryPageProfile;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use Capell\SiteDiscovery\Actions\DiscoverPublicPagesAction;
use Capell\SiteDiscovery\Data\DiscoverablePageData;
use Illuminate\Support\Collection;
use LogicException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static Collection<int, AiDiscoveryPageProfile> run(Site $site, Language $language)
 */
final class SyncAiDiscoveryPageProfilesAction
{
    use AsAction;

    /**
     * @return Collection<int, AiDiscoveryPageProfile>
     */
    public function handle(Site $site, Language $language): Collection
    {
        $siteProfile = ResolveAiDiscoveryProfileAction::run($site, $language);

        throw_unless($siteProfile instanceof AiDiscoverySiteProfile, LogicException::class, 'Resolving an AI Discovery site profile returned an unexpected page profile.');

        $pages = DiscoverPublicPagesAction::run($site, $language);

        return $pages
            ->map(fn (DiscoverablePageData $data): ?Page => $data->page)
            ->filter(fn (?Page $page): bool => $page instanceof Page)
            ->map(fn (Page $page): AiDiscoveryPageProfile => $this->resolvePageProfile($page, $site, $language, $siteProfile))
            ->values();
    }

    private function resolvePageProfile(
        Page $page,
        Site $site,
        Language $language,
        AiDiscoverySiteProfile $siteProfile,
    ): AiDiscoveryPageProfile {
        return ResolveAiDiscoveryPageProfileAction::run($page, $site, $language, $siteProfile);
    }
}
