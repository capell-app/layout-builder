<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\Core\Data\LlmsTxtEntryData;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoTools\Enums\RobotsDirectiveEnum;
use Capell\SeoTools\Support\Sitemap\Queries\PagesForSitemap;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Generate llms.txt content for a site.
 *
 * @method static string run(Site $site, Language $language)
 */
class GenerateLlmsTxtAction
{
    use AsAction;

    public function handle(Site $site, Language $language): string
    {
        $siteName = $site->getMeta('business_name', $site->translation?->title ?? config('app.name'));
        $siteDescription = $site->translation?->meta['description'] ?? '';

        $entries = $this->getPageEntries($site, $language);

        $lines = [];
        $lines[] = '# ' . $siteName;

        if ($siteDescription !== '' && $siteDescription !== '0') {
            $lines[] = '> ' . $siteDescription;
        }

        $lines[] = '';
        $lines[] = '## Pages';

        foreach ($entries as $entry) {
            $lines[] = $entry->toMarkdownLine();
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @return Collection<int, LlmsTxtEntryData>
     */
    private function getPageEntries(Site $site, Language $language): Collection
    {
        $pages = resolve(PagesForSitemap::class)->get($site, $language);

        return $pages
            ->filter(function (Page $page): bool {
                $robots = (array) $page->getMeta('robots', []);

                return ! in_array(RobotsDirectiveEnum::NoIndex->value, $robots, true);
            })
            ->map(fn (Page $page): LlmsTxtEntryData => new LlmsTxtEntryData(
                title: strip_tags($page->translation?->title ?? $page->translation?->label ?? ''),
                url: $page->pageUrl?->full_url ?? '',
                description: strip_tags($page->translation?->meta_description ?? ''),
            ))
            ->filter(fn (LlmsTxtEntryData $entry): bool => $entry->url !== '' && $entry->title !== '')
            ->values();
    }
}
