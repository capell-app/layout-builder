<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\SeoSuite\Data\PageSeoReportData;
use Capell\SeoSuite\Data\SeoIssueData;
use Capell\SeoSuite\Data\SeoPreviewData;
use Capell\SeoSuite\Enums\RobotsDirectiveEnum;
use Capell\SeoSuite\Enums\SeoCheckKeyEnum;
use Capell\SeoSuite\Enums\SeoIssueSeverityEnum;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

/**
 * @method static PageSeoReportData run(Page $page, Site $site, Language $language)
 */
final class BuildPageSeoReportAction
{
    use AsAction;

    public function handle(Page $page, Site $site, Language $language): PageSeoReportData
    {
        $page->load([
            'translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id),
            'pageUrl' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id),
            'pageUrl.siteDomain',
            'site',
            'translations',
        ]);

        $site->load([
            'translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id),
        ]);

        $issues = [];
        $metaTitle = $this->metaValue($page, 'title');
        $metaDescription = $this->metaValue($page, 'description');

        $this->addLengthIssue(
            issues: $issues,
            key: SeoCheckKeyEnum::MetaTitle,
            value: $metaTitle,
            minimum: 30,
            maximum: 70,
            missingMessage: __('capell-seo-suite::generic.seo_issue_meta_title_missing'),
            shortMessage: __('capell-seo-suite::generic.seo_issue_meta_title_short'),
            longMessage: __('capell-seo-suite::generic.seo_issue_meta_title_long'),
        );

        $this->addLengthIssue(
            issues: $issues,
            key: SeoCheckKeyEnum::MetaDescription,
            value: $metaDescription,
            minimum: 50,
            maximum: 160,
            missingMessage: __('capell-seo-suite::generic.seo_issue_meta_description_missing'),
            shortMessage: __('capell-seo-suite::generic.seo_issue_meta_description_short'),
            longMessage: __('capell-seo-suite::generic.seo_issue_meta_description_long'),
        );

        if ($metaTitle !== null && $this->duplicateTitleExists($page, $site, $language, $metaTitle)) {
            $issues[] = new SeoIssueData(
                key: SeoCheckKeyEnum::DuplicateTitle,
                severity: SeoIssueSeverityEnum::Warning,
                message: __('capell-seo-suite::generic.seo_issue_duplicate_title'),
            );
        }

        if ($this->hasNoIndexDirective($page)) {
            $issues[] = new SeoIssueData(
                key: SeoCheckKeyEnum::Robots,
                severity: SeoIssueSeverityEnum::Warning,
                message: __('capell-seo-suite::generic.seo_issue_robots_noindex'),
            );
        }

        $searchTitle = $metaTitle
            ?? $this->stringValue($page->translation?->title)
            ?? $this->stringValue($page->translation?->label)
            ?? $this->stringValue($page->name)
            ?? '';
        $searchDescription = $metaDescription ?? '';
        $previewUrl = $this->previewUrl($page);
        $siteName = $this->stringValue($site->translation?->title);

        $searchPreview = new SeoPreviewData(
            title: $searchTitle,
            description: $searchDescription,
            url: $previewUrl,
            siteName: $siteName,
        );

        $socialPreview = new SeoPreviewData(
            title: $this->metaValue($page, 'social_title') ?? $searchTitle,
            description: $this->metaValue($page, 'social_description') ?? $searchDescription,
            url: $previewUrl,
            imageUrl: null,
            siteName: $siteName,
        );

        return new PageSeoReportData(
            score: CalculateSeoScoreAction::run($issues),
            searchPreview: $searchPreview,
            socialPreview: $socialPreview,
            issues: $issues,
            passedChecks: $this->passedChecks($issues),
            internalLinkSuggestions: SuggestInternalLinksAction::run($page, $site, $language),
            schemaDashboardReports: BuildSchemaTemplateReportAction::run($page, $site, $language),
            redirectOpportunities: BuildRedirectOpportunityReportAction::run($site->id, $language->id, (int) $page->getKey()),
            searchConsoleInsights: BuildPageSearchConsoleInsightsAction::run($page),
            canonicalUrl: $this->metaValue($page, 'canonical_url') ?? $previewUrl,
            robotsDirectives: $this->robotsDirectives($page),
        );
    }

    private function metaValue(Page $page, string $key): ?string
    {
        $translation = $page->translation;

        if (! $translation instanceof Translation) {
            return null;
        }

        $value = method_exists($translation, 'getMeta')
            ? $translation->getMeta($key)
            : ($translation->meta[$key] ?? null);

        return $this->stringValue($value);
    }

    /**
     * @param  list<SeoIssueData>  $issues
     */
    private function addLengthIssue(
        array &$issues,
        SeoCheckKeyEnum $key,
        ?string $value,
        int $minimum,
        int $maximum,
        string $missingMessage,
        string $shortMessage,
        string $longMessage,
    ): void {
        if ($value === null) {
            $issues[] = new SeoIssueData(
                key: $key,
                severity: SeoIssueSeverityEnum::Critical,
                message: $missingMessage,
            );

            return;
        }

        $length = mb_strlen($value);

        if ($length < $minimum) {
            $issues[] = new SeoIssueData(
                key: $key,
                severity: SeoIssueSeverityEnum::Warning,
                message: $shortMessage,
            );

            return;
        }

        if ($length > $maximum) {
            $issues[] = new SeoIssueData(
                key: $key,
                severity: SeoIssueSeverityEnum::Warning,
                message: $longMessage,
            );
        }
    }

    private function duplicateTitleExists(Page $page, Site $site, Language $language, string $title): bool
    {
        return Page::query()
            ->where('site_id', $site->id)
            ->whereKeyNot($page->getKey())
            ->whereHas('translations', function (BuilderContract $query) use ($language, $title): void {
                $query
                    ->where('language_id', $language->id)
                    ->where('meta->title', $title);
            })
            ->exists();
    }

    private function hasNoIndexDirective(Page $page): bool
    {
        return in_array(RobotsDirectiveEnum::NoIndex->value, $this->robotsDirectives($page), true);
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
            array_map($this->stringValue(...), $directives),
            fn (?string $directive): bool => $directive !== null,
        ));
    }

    /**
     * @param  list<SeoIssueData>  $issues
     * @return list<SeoIssueData>
     */
    private function passedChecks(array $issues): array
    {
        $issueKeys = collect($issues)->map(fn (SeoIssueData $issue): SeoCheckKeyEnum => $issue->key);
        $passedChecks = [];

        foreach ([SeoCheckKeyEnum::MetaTitle, SeoCheckKeyEnum::MetaDescription, SeoCheckKeyEnum::DuplicateTitle, SeoCheckKeyEnum::Robots] as $checkKey) {
            if ($issueKeys->contains($checkKey)) {
                continue;
            }

            $passedChecks[] = new SeoIssueData(
                key: $checkKey,
                severity: SeoIssueSeverityEnum::Passed,
                message: __('capell-seo-suite::generic.seo_check_passed', ['check' => $checkKey->getLabel()]),
            );
        }

        return $passedChecks;
    }

    private function previewUrl(Page $page): string
    {
        if ($page->pageUrl === null) {
            return '';
        }

        try {
            return $this->stringValue($page->pageUrl->full_url) ?? $this->stringValue($page->pageUrl->url) ?? '';
        } catch (Throwable) {
            return $this->stringValue($page->pageUrl->url) ?? '';
        }
    }

    private function stringValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $stringValue = trim(strip_tags((string) $value));

        return $stringValue !== '' ? $stringValue : null;
    }
}
