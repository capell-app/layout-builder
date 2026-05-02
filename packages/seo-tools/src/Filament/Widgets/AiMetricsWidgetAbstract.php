<?php

declare(strict_types=1);

namespace Capell\SeoTools\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\SeoTools\Data\Dashboard\AiMetricsData;
use Capell\SeoTools\Data\Dashboard\FeatureUsageData;
use Capell\SeoTools\Models\AIGenerationHistory;
use Capell\SeoTools\Settings\AssistantSettings;
use Capell\SeoTools\Support\AiRateLimiter;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

final class AiMetricsWidgetAbstract extends Widget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    protected static string $settingsKey = 'ai_metrics';

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['developer', 'admin', 'super_admin'];

    protected string $view = 'capell-seo-tools::filament.widgets.ai-metrics';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

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
        $rateLimiter = resolve(AiRateLimiter::class);

        // Total counts
        $totalGenerations = AIGenerationHistory::query()->count();
        $totalTokens = AIGenerationHistory::query()->sum('total_tokens') ?? 0;
        $failedGenerations = AIGenerationHistory::query()
            ->whereNotNull('error_message')
            ->count();

        // Rate limit status
        $remainingRequests = $rateLimiter->getRemainingRequests('global');
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
     * @return Collection<int, FeatureUsageData>
     */
    private function getFeatureUsage(): Collection
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

        return collect($featureData);
    }
}
