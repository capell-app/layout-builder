<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\SeoSuite\Models\AiDiscoveryPageProfile;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use Illuminate\Support\Str;
use LogicException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static AiDiscoveryPageProfile run(Page $page, Site $site, Language $language)
 */
final class FillAiDiscoveryPageSummaryAction
{
    use AsAction;

    public function handle(Page $page, Site $site, Language $language): AiDiscoveryPageProfile
    {
        $profile = $this->profileFor($page, $site, $language);
        $profile->summary = $this->summaryFor($page, $language);
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

    private function summaryFor(Page $page, Language $language): string
    {
        $translation = $this->translationFor($page, $language);
        $meta = (array) $translation?->meta;

        foreach ([
            $meta['summary'] ?? null,
            $translation?->meta_description,
            $translation?->title,
            $page->name,
        ] as $candidate) {
            $summary = trim(strip_tags(is_scalar($candidate) ? (string) $candidate : ''));

            if ($summary !== '') {
                return Str::limit($summary, 280, '');
            }
        }

        return Str::limit($page->name, 280, '');
    }

    private function translationFor(Page $page, Language $language): ?Translation
    {
        if ($page->relationLoaded('translations')) {
            $translation = $page->translations->firstWhere('language_id', $language->getKey());

            return $translation instanceof Translation ? $translation : null;
        }

        $translation = $page->translations()
            ->where('language_id', $language->getKey())
            ->first();

        return $translation instanceof Translation ? $translation : null;
    }
}
