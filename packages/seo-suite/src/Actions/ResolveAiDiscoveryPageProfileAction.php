<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Models\AiDiscoveryPageProfile;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static AiDiscoveryPageProfile run(Page $page, Site $site, Language $language, AiDiscoverySiteProfile $siteProfile)
 */
final class ResolveAiDiscoveryPageProfileAction
{
    use AsAction;

    public function handle(
        Page $page,
        Site $site,
        Language $language,
        AiDiscoverySiteProfile $siteProfile,
    ): AiDiscoveryPageProfile {
        $profile = AiDiscoveryPageProfile::query()->firstOrCreate(
            [
                'page_id' => $page->getKey(),
                'site_id' => $site->getKey(),
                'language_id' => $language->getKey(),
            ],
            [
                'include_in_ai_index' => $siteProfile->default_include_pages,
                'section' => $siteProfile->default_section,
                'priority' => 500,
            ],
        );

        $profile->fill($this->pageMetaOverrides($page));

        if ($profile->isDirty()) {
            $profile->save();
        }

        return $profile;
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    private function pageMetaOverrides(Page $page): array
    {
        $settings = (array) ($page->meta['ai_discovery'] ?? []);
        $overrides = [];

        if (array_key_exists('include_in_ai_index', $settings)) {
            $overrides['include_in_ai_index'] = (bool) $settings['include_in_ai_index'];
        }

        foreach (['summary', 'section', 'exclude_reason', 'markdown_override'] as $key) {
            if (array_key_exists($key, $settings)) {
                $value = is_scalar($settings[$key]) ? trim((string) $settings[$key]) : null;
                $overrides[$key] = $value !== '' ? $value : null;
            }
        }

        if (array_key_exists('priority', $settings) && is_numeric($settings['priority'])) {
            $overrides['priority'] = max(0, min(1000, (int) $settings['priority']));
        }

        return $overrides;
    }
}
