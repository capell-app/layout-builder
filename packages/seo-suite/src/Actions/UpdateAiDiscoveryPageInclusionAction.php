<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Models\AiDiscoveryPageProfile;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use LogicException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static AiDiscoveryPageProfile run(Page $page, Site $site, Language $language, bool $includeInAiIndex, ?string $excludeReason = null)
 */
final class UpdateAiDiscoveryPageInclusionAction
{
    use AsAction;

    public function handle(
        Page $page,
        Site $site,
        Language $language,
        bool $includeInAiIndex,
        ?string $excludeReason = null,
    ): AiDiscoveryPageProfile {
        $profile = $this->profileFor($page, $site, $language);
        $profile->include_in_ai_index = $includeInAiIndex;
        $profile->exclude_reason = $includeInAiIndex ? null : $this->excludeReason($excludeReason);
        $profile->save();

        return $profile;
    }

    private function profileFor(Page $page, Site $site, Language $language): AiDiscoveryPageProfile
    {
        $siteProfile = ResolveAiDiscoveryProfileAction::run($site, $language);

        throw_unless($siteProfile instanceof AiDiscoverySiteProfile, LogicException::class, 'AI Discovery site profile could not be resolved.');

        $pageProfile = ResolveAiDiscoveryProfileAction::run($site, $language, $page);

        throw_unless($pageProfile instanceof AiDiscoveryPageProfile, LogicException::class, 'AI Discovery page profile could not be resolved.');

        return $pageProfile;
    }

    private function excludeReason(?string $excludeReason): ?string
    {
        $reason = trim((string) $excludeReason);

        return $reason !== '' ? $reason : null;
    }
}
