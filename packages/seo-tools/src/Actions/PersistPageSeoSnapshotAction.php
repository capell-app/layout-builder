<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoTools\Data\PageSeoReportData;
use Capell\SeoTools\Data\SchemaTemplateReportData;
use Capell\SeoTools\Data\SeoIssueData;
use Capell\SeoTools\Enums\SeoCheckKeyEnum;
use Capell\SeoTools\Enums\SeoIssueSeverityEnum;
use Capell\SeoTools\Enums\SeoSnapshotStatusEnum;
use Capell\SeoTools\Models\PageSeoSnapshot;
use Lorisleiva\Actions\Concerns\AsAction;

final class PersistPageSeoSnapshotAction
{
    use AsAction;

    public function handle(Page $page, Site $site, Language $language, PageSeoReportData $report): PageSeoSnapshot
    {
        $issues = $this->issues($report);
        $issueKeys = $this->issueKeys($issues);
        $passedCheckKeys = $this->passedCheckKeys($report);

        return PageSeoSnapshot::query()->updateOrCreate(
            [
                'page_id' => $page->getKey(),
                'site_id' => $site->getKey(),
                'language_id' => $language->getKey(),
            ],
            [
                'score' => $report->score,
                'critical_count' => $this->countSeverity($issues, SeoIssueSeverityEnum::Critical),
                'warning_count' => $this->countSeverity($issues, SeoIssueSeverityEnum::Warning),
                'notice_count' => $this->countSeverity($issues, SeoIssueSeverityEnum::Notice),
                'passed_count' => count($passedCheckKeys),
                'issue_keys' => $issueKeys,
                'passed_check_keys' => $passedCheckKeys,
                'schema_status' => $this->schemaStatus($report)->value,
                'robots_status' => $this->issueStatus($issues, SeoCheckKeyEnum::Robots)->value,
                'canonical_status' => $this->issueStatus($issues, SeoCheckKeyEnum::Canonical)->value,
                'internal_link_suggestions_count' => count($report->internalLinkSuggestions),
                'redirect_opportunities_count' => count($report->redirectOpportunities),
                'search_console_status' => $this->searchConsoleStatus($issues)->value,
                'computed_at' => now(),
            ],
        );
    }

    /**
     * @return array<int, SeoIssueData>
     */
    private function issues(PageSeoReportData $report): array
    {
        $issues = [];

        foreach ($report->issues as $issue) {
            if ($issue instanceof SeoIssueData) {
                $issues[] = $issue;
            }
        }

        return $issues;
    }

    /**
     * @param  array<int, SeoIssueData>  $issues
     * @return array<int, string>
     */
    private function issueKeys(array $issues): array
    {
        $keys = [];

        foreach ($issues as $issue) {
            $keys[$issue->key->value] = $issue->key->value;
        }

        return array_values($keys);
    }

    /**
     * @return array<int, string>
     */
    private function passedCheckKeys(PageSeoReportData $report): array
    {
        $keys = [];

        foreach ($report->passedChecks as $passedCheck) {
            if ($passedCheck instanceof SeoCheckKeyEnum) {
                $keys[$passedCheck->value] = $passedCheck->value;

                continue;
            }

            if ($passedCheck instanceof SeoIssueData) {
                $keys[$passedCheck->key->value] = $passedCheck->key->value;
            }
        }

        return array_values($keys);
    }

    /**
     * @param  array<int, SeoIssueData>  $issues
     */
    private function countSeverity(array $issues, SeoIssueSeverityEnum $severity): int
    {
        $count = 0;

        foreach ($issues as $issue) {
            if ($issue->severity === $severity) {
                $count++;
            }
        }

        return $count;
    }

    private function schemaStatus(PageSeoReportData $report): SeoSnapshotStatusEnum
    {
        if ($report->schemaReports === []) {
            return SeoSnapshotStatusEnum::Missing;
        }

        foreach ($report->schemaReports as $schemaReport) {
            if ($this->schemaReportHasIssue($schemaReport)) {
                return SeoSnapshotStatusEnum::Warning;
            }
        }

        return SeoSnapshotStatusEnum::Passed;
    }

    private function schemaReportHasIssue(mixed $schemaReport): bool
    {
        if (! $schemaReport instanceof SchemaTemplateReportData) {
            return false;
        }

        if ($schemaReport->missingFields !== []) {
            return true;
        }

        return $schemaReport->severity !== SeoIssueSeverityEnum::Passed;
    }

    /**
     * @param  array<int, SeoIssueData>  $issues
     */
    private function issueStatus(array $issues, SeoCheckKeyEnum $key): SeoSnapshotStatusEnum
    {
        foreach ($issues as $issue) {
            if ($issue->key === $key) {
                return SeoSnapshotStatusEnum::Warning;
            }
        }

        return SeoSnapshotStatusEnum::Passed;
    }

    /**
     * @param  array<int, SeoIssueData>  $issues
     */
    private function searchConsoleStatus(array $issues): SeoSnapshotStatusEnum
    {
        foreach ($issues as $issue) {
            if ($issue->key === SeoCheckKeyEnum::SearchConsole) {
                return SeoSnapshotStatusEnum::Warning;
            }
        }

        return SeoSnapshotStatusEnum::Unknown;
    }
}
