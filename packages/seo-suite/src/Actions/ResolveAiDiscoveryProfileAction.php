<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\SeoSuite\Enums\AiDiscoveryStatusEnum;
use Capell\SeoSuite\Models\AiDiscoveryPageProfile;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use Capell\SeoSuite\Settings\SeoSuiteSettings;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

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

        $siteProfile->fill($this->siteMetaOverrides($site, $language));

        if ($siteProfile->isDirty()) {
            $siteProfile->save();
        }

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
        $enabledByDefault = $this->settings()->ai_discovery_default_enabled ?? true;

        return [
            'llms_txt_enabled' => $enabledByDefault,
            'llms_full_txt_enabled' => false,
            'markdown_pages_enabled' => $enabledByDefault,
            'accept_markdown_enabled' => false,
            'default_include_pages' => true,
            'max_full_txt_pages' => 50,
            'max_full_txt_bytes' => 250000,
            'cache_ttl_seconds' => 3600,
            'default_section' => 'Pages',
            'status' => AiDiscoveryStatusEnum::Enabled->value,
        ];
    }

    private function settings(): ?SeoSuiteSettings
    {
        try {
            return resolve(SeoSuiteSettings::class);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    private function siteMetaOverrides(Site $site, Language $language): array
    {
        $settings = (array) ($this->siteTranslation($site, $language)?->meta['ai_discovery'] ?? []);
        $overrides = [];

        foreach ([
            'llms_txt_enabled',
            'llms_full_txt_enabled',
            'markdown_pages_enabled',
            'accept_markdown_enabled',
            'default_include_pages',
        ] as $key) {
            if (array_key_exists($key, $settings)) {
                $overrides[$key] = (bool) $settings[$key];
            }
        }

        foreach (['max_full_txt_pages', 'max_full_txt_bytes', 'cache_ttl_seconds'] as $key) {
            if (array_key_exists($key, $settings) && is_numeric($settings[$key])) {
                $overrides[$key] = max(0, (int) $settings[$key]);
            }
        }

        foreach (['default_section', 'intro_markdown'] as $key) {
            if (array_key_exists($key, $settings)) {
                $value = is_scalar($settings[$key]) ? trim((string) $settings[$key]) : null;
                $overrides[$key] = $value !== '' ? $value : null;
            }
        }

        if (array_key_exists('status', $settings)) {
            $status = AiDiscoveryStatusEnum::tryFrom((string) $settings['status']);

            if ($status instanceof AiDiscoveryStatusEnum) {
                $overrides['status'] = $status->value;
            }
        }

        return $overrides;
    }

    private function siteTranslation(Site $site, Language $language): ?Translation
    {
        if ($site->relationLoaded('translations')) {
            $translation = $site->translations->firstWhere('language_id', $language->getKey());

            return $translation instanceof Translation ? $translation : null;
        }

        $translation = $site->translations()
            ->where('language_id', $language->getKey())
            ->first();

        return $translation instanceof Translation ? $translation : null;
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
