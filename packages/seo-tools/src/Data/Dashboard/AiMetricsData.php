<?php

declare(strict_types=1);

namespace Capell\SeoTools\Data\Dashboard;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class AiMetricsData extends Data
{
    /**
     * @param  Collection<int, FeatureUsageData>  $featureUsage
     */
    public function __construct(
        public readonly int $totalGenerations,
        public readonly int $totalTokens,
        public readonly int $failedGenerations,
        public readonly int $remainingRequests,
        public readonly int $windowLimitSeconds,
        public readonly ?int $lastWindowEnd,
        public readonly string $aiProvider,
        public readonly string $aiModel,
        public readonly bool $pageContentGeneratorEnabled,
        public readonly bool $pageTitleSuggestionsEnabled,
        public readonly bool $aiCreatorEnabled,
        public readonly Collection $featureUsage,
    ) {}
}

final class FeatureUsageData extends Data
{
    public function __construct(
        public readonly string $feature,
        public readonly int $count,
        public readonly int $tokens,
        public readonly float $averageTokensPerRequest,
    ) {}
}
