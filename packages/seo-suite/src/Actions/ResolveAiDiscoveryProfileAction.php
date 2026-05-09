<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Enums\AiDiscoveryStatusEnum;
use Capell\SeoSuite\Models\AiDiscoveryPageProfile;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static AiDiscoverySiteProfile|AiDiscoveryPageProfile run(Site $site, Language $language, ?Page $page = null)
 */
final class ResolveAiDiscoveryProfileAction
{
    use AsAction;

    public function handle(Site $site, Language $language, ?Page $page = null): AiDiscoverySiteProfile|AiDiscoveryPageProfile
    {
        $siteProfile = AiDiscoverySiteProfile::query()->firstOrCreate(
            [
                'site_id' => $site->getKey(),
                'language_id' => $language->getKey(),
            ],
            $this->siteProfileDefaults(),
        );

        if (! $page instanceof Page) {
            return $siteProfile;
        }

        return AiDiscoveryPageProfile::query()->firstOrCreate(
            [
                'page_id' => $page->getKey(),
                'site_id' => $site->getKey(),
                'language_id' => $language->getKey(),
            ],
            $this->pageProfileDefaults($siteProfile),
        );
    }

    /**
     * @return array<string, bool|int|string>
     */
    private function siteProfileDefaults(): array
    {
        return [
            'llms_txt_enabled' => true,
            'llms_full_txt_enabled' => false,
            'markdown_pages_enabled' => true,
            'accept_markdown_enabled' => false,
            'default_include_pages' => true,
            'max_full_txt_pages' => 50,
            'max_full_txt_bytes' => 250000,
            'cache_ttl_seconds' => 3600,
            'default_section' => 'Pages',
            'status' => AiDiscoveryStatusEnum::Enabled->value,
        ];
    }

    /**
     * @return array<string, bool|int|string>
     */
    private function pageProfileDefaults(AiDiscoverySiteProfile $siteProfile): array
    {
        return [
            'include_in_ai_index' => $siteProfile->default_include_pages,
            'section' => $siteProfile->default_section,
            'priority' => 500,
        ];
    }
}
