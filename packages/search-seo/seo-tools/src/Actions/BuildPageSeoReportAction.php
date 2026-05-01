<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\SeoTools\Data\PageSeoReportData;
use Capell\SeoTools\Data\SeoIssueData;
use Capell\SeoTools\Data\SeoPreviewData;
use Capell\SeoTools\Enums\RobotsDirectiveEnum;
use Capell\SeoTools\Enums\SeoCheckKeyEnum;
use Capell\SeoTools\Enums\SeoIssueSeverityEnum;
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
            missingMessage: __('capell-seo-tools::generic.seo_issue_meta_title_missing'),
            shortMessage: __('capell-seo-tools::generic.seo_issue_meta_title_short'),
            longMessage: __('capell-seo-tools::generic.seo_issue_meta_title_long'),
        );

        $this->addLengthIssue(
            issues: $issues,
            key: SeoCheckKeyEnum::MetaDescription,
            value: $metaDescription,
            minimum: 50,
            maximum: 160,
            missingMessage: __('capell-seo-tools::generic.seo_issue_meta_description_missing'),
            shortMessage: __('capell-seo-tools::generic.seo_issue_meta_description_short'),
            longMessage: __('capell-seo-tools::generic.seo_issue_meta_description_long'),
        );

        if ($metaTitle !== null && $this->duplicateTitleExists($page, $site, $language, $metaTitle)) {
            $issues[] = new SeoIssueData(
                key: SeoCheckKeyEnum::DuplicateTitle,
                severity: SeoIssueSeverityEnum::Warning,
                message: __('capell-seo-tools::generic.seo_issue_duplicate_title'),
            );
        }

        if ($this->hasNoIndexDirective($page)) {
            $issues[] = new SeoIssueData(
                key: SeoCheckKeyEnum::Robots,
                severity: SeoIssueSeverityEnum::Warning,
                message: __('capell-seo-tools::generic.seo_issue_robots_noindex'),
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
            passedChecks: [],
            internalLinkSuggestions: [],
            schemaReports: [],
            redirectOpportunities: [],
            searchConsoleInsights: [],
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
        $directives = method_exists($page, 'getMeta')
            ? $page->getMeta('robots', [])
            : ($page->meta['robots'] ?? []);

        if (is_string($directives)) {
            $directives = [$directives];
        }

        if (! is_array($directives)) {
            return false;
        }

        return in_array(RobotsDirectiveEnum::NoIndex->value, $directives, true);
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
