<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Page;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Translation;
use Capell\SeoSuite\Data\AiDiscoveryPageEntryData;
use Capell\SeoSuite\Data\AiDiscoveryRenderContextData;
use Capell\SeoSuite\Models\AiDiscoveryPageProfile;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use Capell\SiteDiscovery\Actions\DiscoverPublicPagesAction;
use Capell\SiteDiscovery\Data\DiscoverablePageData;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static Collection<int, AiDiscoveryPageEntryData> run(AiDiscoveryRenderContextData $context, AiDiscoverySiteProfile $siteProfile)
 */
final class BuildAiDiscoveryPageEntriesAction
{
    use AsAction;

    /**
     * @return Collection<int, AiDiscoveryPageEntryData>
     */
    public function handle(AiDiscoveryRenderContextData $context, AiDiscoverySiteProfile $siteProfile): Collection
    {
        $pages = $this->pagesForDiscovery($context);
        $profiles = $this->profilesForPages($context, $pages);

        return $pages
            ->map(fn (Page $page): ?AiDiscoveryPageEntryData => $this->entryForPage($page, $profiles, $context, $siteProfile))
            ->filter(fn (?AiDiscoveryPageEntryData $entry): bool => $entry instanceof AiDiscoveryPageEntryData)
            ->sortBy([
                ['section', 'asc'],
                ['priority', 'asc'],
            ])
            ->values();
    }

    /**
     * @return EloquentCollection<int, Page>
     */
    private function pagesForDiscovery(AiDiscoveryRenderContextData $context): EloquentCollection
    {
        return new EloquentCollection(
            DiscoverPublicPagesAction::run($context->site, $context->language)
                ->map(fn (DiscoverablePageData $data): ?Page => $data->page)
                ->filter(fn (?Page $page): bool => $page instanceof Page)
                ->values()
                ->all(),
        );
    }

    /**
     * @param  EloquentCollection<int, Page>  $pages
     * @return Collection<int, AiDiscoveryPageProfile>
     */
    private function profilesForPages(AiDiscoveryRenderContextData $context, EloquentCollection $pages): Collection
    {
        return AiDiscoveryPageProfile::query()
            ->where('site_id', $context->site->getKey())
            ->where('language_id', $context->language->getKey())
            ->whereIn('page_id', $pages->pluck('id')->all())
            ->get()
            ->keyBy('page_id');
    }

    /**
     * @param  Collection<int, AiDiscoveryPageProfile>  $profiles
     */
    private function entryForPage(
        Page $page,
        Collection $profiles,
        AiDiscoveryRenderContextData $context,
        AiDiscoverySiteProfile $siteProfile,
    ): ?AiDiscoveryPageEntryData {
        $profile = $profiles->get($page->getKey());

        if (! $profile instanceof AiDiscoveryPageProfile || ! $profile->include_in_ai_index) {
            return null;
        }

        if ($context->siteDomain instanceof SiteDomain && $page->pageUrl !== null) {
            $page->pageUrl->setRelation('siteDomain', $context->siteDomain);
        }

        $url = $page->pageUrl?->full_url ?? '';
        $title = trim(strip_tags($page->translation?->title ?? $page->translation?->label ?? ''));

        if ($url === '' || $title === '') {
            return null;
        }

        return new AiDiscoveryPageEntryData(
            title: $title,
            url: $url,
            markdownUrl: $siteProfile->markdown_pages_enabled ? $this->markdownUrl($url) : null,
            description: $this->description($profile, $page->translation),
            section: $this->section($profile, $siteProfile),
            priority: $profile->priority,
            pageId: (int) $page->getKey(),
        );
    }

    private function description(AiDiscoveryPageProfile $profile, ?Translation $translation): ?string
    {
        $summary = trim((string) $profile->summary);

        if ($summary !== '') {
            return $summary;
        }

        $metaDescription = trim((string) $translation?->meta_description);

        if ($metaDescription !== '') {
            return $metaDescription;
        }

        $meta = (array) $translation?->meta;
        $description = trim((string) ($meta['description'] ?? ''));

        return $description !== '' ? $description : null;
    }

    private function section(AiDiscoveryPageProfile $profile, AiDiscoverySiteProfile $siteProfile): string
    {
        $section = trim($profile->section);

        if ($section !== '') {
            return $section;
        }

        return trim($siteProfile->default_section) !== ''
            ? $siteProfile->default_section
            : 'Pages';
    }

    private function markdownUrl(string $url): string
    {
        $trimmedUrl = mb_rtrim($url, '/');
        $path = parse_url($trimmedUrl, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return $trimmedUrl . '/index.md';
        }

        return $trimmedUrl . '.md';
    }
}
