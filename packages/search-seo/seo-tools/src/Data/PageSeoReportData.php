<?php

declare(strict_types=1);

namespace Capell\SeoTools\Data;

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
}
