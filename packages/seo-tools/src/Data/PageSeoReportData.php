<?php

declare(strict_types=1);

namespace Capell\SeoTools\Data;

use Capell\SeoTools\Enums\SeoCheckKeyEnum;
use Capell\SeoTools\Enums\SeoIssueSeverityEnum;
use Spatie\LaravelData\Data;

class PageSeoReportData extends Data
{
    public function __construct(
        public int $score,
        public SeoPreviewData $searchPreview,
        public SeoPreviewData $socialPreview,
        public array $issues = [],
        public array $passedChecks = [],
        public array $internalLinkSuggestions = [],
        public array $schemaReports = [],
        public array $redirectOpportunities = [],
        public array $searchConsoleInsights = [],
        public ?string $canonicalUrl = null,
        public array $robotsDirectives = [],
    ) {}

    public function criticalCount(): int
    {
        return count(array_filter(
            $this->issues,
            fn (SeoIssueData $issue): bool => $issue->severity === SeoIssueSeverityEnum::Critical,
        ));
    }

    public function warningCount(): int
    {
        return count(array_filter(
            $this->issues,
            fn (SeoIssueData $issue): bool => $issue->severity === SeoIssueSeverityEnum::Warning,
        ));
    }

    /**
     * @return array<int, SeoIssueData>
     */
    public function issuesBySeverity(SeoIssueSeverityEnum $severity): array
    {
        return array_values(array_filter(
            $this->issues,
            fn (SeoIssueData $issue): bool => $issue->severity === $severity,
        ));
    }

    /**
     * @return array<int, SeoIssueData>
     */
    public function issuesForKey(SeoCheckKeyEnum $key): array
    {
        return array_values(array_filter(
            $this->issues,
            fn (SeoIssueData $issue): bool => $issue->key === $key,
        ));
    }

    public function hasIssuesForKey(SeoCheckKeyEnum $key): bool
    {
        return $this->issuesForKey($key) !== [];
    }

    /**
     * @return list<string>
     */
    public function passedCheckValues(): array
    {
        $values = [];

        foreach ($this->passedChecks as $passedCheck) {
            if ($passedCheck instanceof SeoCheckKeyEnum) {
                $values[] = $passedCheck->value;

                continue;
            }

            if ($passedCheck instanceof SeoIssueData) {
                $values[] = $passedCheck->key->value;

                continue;
            }

            if (is_string($passedCheck) && trim($passedCheck) !== '') {
                $values[] = $passedCheck;
            }
        }

        return array_values($values);
    }
}
