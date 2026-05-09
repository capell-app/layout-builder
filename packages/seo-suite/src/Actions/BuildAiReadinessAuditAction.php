<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Actions\Content\ExtractTextContentAction;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\SeoSuite\Data\AiReadinessIssueData;
use Capell\SeoSuite\Enums\RobotsDirectiveEnum;
use Capell\SeoSuite\Models\AiDiscoveryPageProfile;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Support\Collection;
use LogicException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static Collection<int, AiReadinessIssueData> run(Page $page, Site $site, Language $language)
 */
final class BuildAiReadinessAuditAction
{
    use AsAction;

    /**
     * @return Collection<int, AiReadinessIssueData>
     */
    public function handle(Page $page, Site $site, Language $language): Collection
    {
        $page->loadMissing([
            'translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->getKey()),
            'pageUrl' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->getKey()),
        ]);

        $siteProfile = ResolveAiDiscoveryProfileAction::run($site, $language);
        $pageProfile = ResolveAiDiscoveryProfileAction::run($site, $language, $page);

        throw_unless($siteProfile instanceof AiDiscoverySiteProfile, LogicException::class, 'Resolving an AI Discovery site profile returned an unexpected page profile.');
        throw_unless($pageProfile instanceof AiDiscoveryPageProfile, LogicException::class, 'Resolving an AI Discovery page profile returned an unexpected site profile.');

        $issues = collect();
        $translation = $page->translation;

        if (trim((string) $pageProfile->summary) === '') {
            $issues->push($this->issue('missing_summary', 'warning', 'Add an AI summary for this page.', $page));
        }

        if (mb_strlen($this->title($page, $translation)) < 30) {
            $issues->push($this->issue('weak_title', 'warning', 'Use a clearer, more specific title.', $page));
        }

        if ($this->canonicalUrl($page, $translation) === '') {
            $issues->push($this->issue('missing_canonical', 'warning', 'Add a canonical URL or ensure the page URL is available.', $page));
        }

        if (! $this->hasSchema($page, $translation)) {
            $issues->push($this->issue('missing_schema', 'notice', 'Add schema for entity clarity where appropriate.', $page));
        }

        if (trim($this->extractTextContent($translation)) === '') {
            $issues->push($this->issue('js_only_content', 'warning', 'Expose meaningful server-rendered text content.', $page));
        }

        if (! $siteProfile->markdown_pages_enabled) {
            $issues->push($this->issue('missing_markdown_view', 'warning', 'Enable Markdown page views for this site language.', $page));
        }

        if ($this->duplicateTitleExists($page, $site, $language, $this->title($page, $translation))) {
            $issues->push($this->issue('duplicate_entity_name', 'notice', 'Another page is using the same entity title.', $page));
        }

        if (in_array(RobotsDirectiveEnum::NoIndex->value, $this->robotsDirectives($page), true)) {
            $issues->push($this->issue('excluded_by_noindex', 'warning', 'This page is excluded by noindex.', $page));
        }

        return $issues->values();
    }

    private function issue(string $key, string $severity, string $message, Page $page): AiReadinessIssueData
    {
        return new AiReadinessIssueData($key, $severity, $message, (int) $page->getKey());
    }

    private function title(Page $page, ?Translation $translation): string
    {
        $meta = (array) $translation?->meta;

        return trim(strip_tags((string) ($meta['title'] ?? $translation?->title ?? $page->name)));
    }

    private function canonicalUrl(Page $page, ?Translation $translation): string
    {
        $meta = (array) $translation?->meta;

        return trim((string) ($meta['canonical_url'] ?? $page->pageUrl?->full_url ?? ''));
    }

    private function hasSchema(Page $page, ?Translation $translation): bool
    {
        $pageMeta = (array) $page->meta;
        $translationMeta = (array) $translation?->meta;

        return filled($pageMeta['schema'] ?? null)
            || filled($pageMeta['schema_templates'] ?? null)
            || filled($translationMeta['schema'] ?? null)
            || filled($translationMeta['schema_templates'] ?? null);
    }

    private function extractableContent(?Translation $translation): string|array|null
    {
        $content = $translation?->content;

        return is_string($content) || is_array($content) ? $content : null;
    }

    private function extractTextContent(?Translation $translation): string
    {
        return ExtractTextContentAction::run($this->extractableContent($translation));
    }

    private function duplicateTitleExists(Page $page, Site $site, Language $language, string $title): bool
    {
        if ($title === '') {
            return false;
        }

        return Page::query()
            ->where('site_id', $site->getKey())
            ->whereKeyNot($page->getKey())
            ->whereHas('translations', function (BuilderContract $query) use ($language, $title): void {
                $query
                    ->where('language_id', $language->getKey())
                    ->where(
                        fn (BuilderContract $titleQuery): BuilderContract => $titleQuery
                            ->where('title', $title)
                            ->orWhere('meta->title', $title),
                    );
            })
            ->exists();
    }

    /**
     * @return list<string>
     */
    private function robotsDirectives(Page $page): array
    {
        $directives = method_exists($page, 'getMeta')
            ? $page->getMeta('robots', [])
            : ($page->meta['robots'] ?? []);

        if (is_string($directives)) {
            $directives = [$directives];
        }

        if (! is_array($directives)) {
            return [];
        }

        return array_values(array_filter(
            array_map(
                fn (mixed $directive): ?string => is_scalar($directive) ? trim((string) $directive) : null,
                $directives,
            ),
            fn (?string $directive): bool => $directive !== null && $directive !== '',
        ));
    }
}
