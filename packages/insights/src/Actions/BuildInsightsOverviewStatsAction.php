<?php

declare(strict_types=1);

namespace Capell\Insights\Actions;

use Capell\Insights\Data\InsightsWindowData;
use Capell\Insights\Enums\InsightsEventType;
use Capell\Insights\Models\InsightsEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildInsightsOverviewStatsAction
{
    use AsAction;

    /**
     * @return Collection<int, array{id: string, label: string, value: int}>
     */
    public function handle(InsightsWindowData $window): Collection
    {
        return collect([
            [
                'id' => 'page-views',
                'label' => __('capell-insights::widgets.page_views'),
                'value' => $this->countEvents($window, InsightsEventType::PageView),
            ],
            [
                'id' => 'unique-visits',
                'label' => __('capell-insights::widgets.unique_visits'),
                'value' => $this->countUniqueVisits($window),
            ],
            [
                'id' => 'clicks',
                'label' => __('capell-insights::widgets.clicks'),
                'value' => $this->countEvents($window, InsightsEventType::Click),
            ],
        ]);
    }

    private function countEvents(InsightsWindowData $window, InsightsEventType $type): int
    {
        return InsightsEvent::query()
            ->where('type', $type)
            ->whereBetween('occurred_at', [$window->startsAt, $window->endsAt])
            ->when($window->siteId !== null, fn (Builder $builder): Builder => $builder->where('site_id', $window->siteId))
            ->when($window->languageId !== null, fn (Builder $builder): Builder => $builder->where('language_id', $window->languageId))
            ->count();
    }

    private function countUniqueVisits(InsightsWindowData $window): int
    {
        return InsightsEvent::query()
            ->whereBetween('occurred_at', [$window->startsAt, $window->endsAt])
            ->when($window->siteId !== null, fn (Builder $builder): Builder => $builder->where('site_id', $window->siteId))
            ->when($window->languageId !== null, fn (Builder $builder): Builder => $builder->where('language_id', $window->languageId))
            ->whereNotNull('visit_id')
            ->distinct('visit_id')
            ->count('visit_id');
    }
}
