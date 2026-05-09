<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Data\AiDiscoveryPageEntryData;
use Capell\SeoSuite\Data\AiDiscoveryRenderContextData;
use Capell\SeoSuite\Enums\AiDiscoveryStatusEnum;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static string run(AiDiscoveryRenderContextData|Site $context, ?Language $language = null)
 */
final class GenerateLlmsFullTxtAction
{
    use AsAction;

    public function handle(AiDiscoveryRenderContextData|Site $context, ?Language $language = null): string
    {
        $renderContext = $this->renderContext($context, $language);
        $siteProfile = ResolveAiDiscoveryProfileAction::run($renderContext->site, $renderContext->language);

        throw_unless($siteProfile instanceof AiDiscoverySiteProfile, LogicException::class, 'Resolving an AI Discovery site profile returned an unexpected page profile.');

        if (! $siteProfile->llms_full_txt_enabled || $siteProfile->status === AiDiscoveryStatusEnum::Disabled) {
            return '';
        }

        SyncAiDiscoveryPageProfilesAction::run($renderContext->site, $renderContext->language);

        $content = GenerateLlmsTxtAction::run($renderContext);
        $byteLimit = $siteProfile->max_full_txt_bytes;
        $entries = BuildAiDiscoveryPageEntriesAction::run($renderContext, $siteProfile)
            ->take($siteProfile->max_full_txt_pages);
        $pages = $this->pagesForEntries($entries);

        foreach ($entries as $entry) {
            $page = $pages->get($entry->pageId);

            if (! $page instanceof Page) {
                continue;
            }

            $pageMarkdown = GeneratePageMarkdownAction::run($renderContext, $page);

            if ($pageMarkdown === '') {
                continue;
            }

            $section = "\n---\n\n" . rtrim($pageMarkdown) . "\n";

            if (strlen($content . $section) > $byteLimit) {
                break;
            }

            $content .= $section;
        }

        return rtrim($content) . "\n";
    }

    /**
     * @param  Collection<int, AiDiscoveryPageEntryData>  $entries
     * @return Collection<int, Page>
     */
    private function pagesForEntries(Collection $entries): Collection
    {
        $pageIds = $entries
            ->pluck('pageId')
            ->filter(fn (?int $pageId): bool => $pageId !== null)
            ->values();

        if ($pageIds->isEmpty()) {
            return collect();
        }

        return Page::query()
            ->whereKey($pageIds->all())
            ->get()
            ->keyBy(fn (Page $page): int => (int) $page->getKey());
    }

    private function renderContext(AiDiscoveryRenderContextData|Site $context, ?Language $language): AiDiscoveryRenderContextData
    {
        if ($context instanceof AiDiscoveryRenderContextData) {
            return $context;
        }

        throw_unless($language instanceof Language, InvalidArgumentException::class, 'A language is required when generating llms-full.txt from a site.');

        return new AiDiscoveryRenderContextData(site: $context, language: $language);
    }
}
