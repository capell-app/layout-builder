<?php

declare(strict_types=1);

namespace Capell\Assistant\Filament\Widgets;

use Capell\Admin\Filament\Widgets\CapellWidget;
use Capell\Assistant\Data\Dashboard\AiMetricsData;
use Capell\Assistant\Data\Dashboard\FeatureUsageData;
use Capell\Assistant\Models\AIGenerationHistory;
use Capell\Assistant\Settings\AssistantSettings;
use Capell\Assistant\Support\Cache\RateLimitCache;
use Spatie\LaravelData\DataCollection;

final class AiMetricsWidget extends CapellWidget
{
    protected static string $settingsKey = 'ai_metrics';

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['developer', 'admin'];

    protected string $view = 'capell-assistant::filament.widgets.ai-metrics';

    private static ?string $heading = 'AI metrics';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'data' => $this->getData(),
        ];
    }

    private function getData(): AiMetricsData
    {
        $settings = resolve(AssistantSettings::class);
        $rateLimitCache = resolve(RateLimitCache::class);

        // Total counts
        $totalGenerations = AIGenerationHistory::query()->count();
        $totalTokens = AIGenerationHistory::query()->sum('total_tokens') ?? 0;
        $failedGenerations = AIGenerationHistory::query()
            ->where('failed', true)
            ->orWhereNotNull('error_message')
            ->count();

        // Rate limit status
        $remainingRequests = $rateLimitCache->getRemainingRequests('global') ?? 0;
        $windowLimitSeconds = config('assistant.rate_limit.window_seconds', 60);
        $lastWindowEnd = null;

        // Feature usage
        $featureUsage = $this->getFeatureUsage();

        return new AiMetricsData(
            totalGenerations: $totalGenerations,
            totalTokens: (int) $totalTokens,
            failedGenerations: $failedGenerations,
            remainingRequests: $remainingRequests,
            windowLimitSeconds: $windowLimitSeconds,
            lastWindowEnd: $lastWindowEnd,
            aiProvider: $settings->ai_provider ?? 'openai',
            aiModel: $settings->ai_model ?? 'gpt-4-turbo',
            pageContentGeneratorEnabled: $settings->page_content_generator ?? false,
            pageTitleSuggestionsEnabled: $settings->page_title_suggestions ?? false,
            aiCreatorEnabled: $settings->ai_creator ?? false,
            featureUsage: $featureUsage,
        );
    }

    /**
     * @return DataCollection<int, FeatureUsageData>
     */
    private function getFeatureUsage(): DataCollection
    {
        $features = AIGenerationHistory::query()
            ->select('action')
            ->distinct()
            ->pluck('action');

        $featureData = [];
        foreach ($features as $feature) {
            $count = AIGenerationHistory::query()
                ->where('action', $feature)
                ->count();
            $tokens = AIGenerationHistory::query()
                ->where('action', $feature)
                ->sum('total_tokens') ?? 0;

            $featureData[] = new FeatureUsageData(
                feature: $feature,
                count: $count,
                tokens: (int) $tokens,
                averageTokensPerRequest: $count > 0 ? $tokens / $count : 0,
            );
        }

        // Sort by count descending
        usort($featureData, fn (FeatureUsageData $a, FeatureUsageData $b): int => $b->count <=> $a->count);

        return DataCollection::from($featureData);
    }
}
