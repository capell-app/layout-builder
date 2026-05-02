<?php

declare(strict_types=1);

namespace Capell\Analytics\Actions;

use Capell\Analytics\Data\AnalyticsWindowData;
use Capell\Analytics\Enums\AnalyticsEventType;
use Capell\Analytics\Models\AnalyticsEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildAnalyticsOverviewStatsAction
{
    use AsAction;

    /**
     * @return Collection<int, array{id: string, label: string, value: int}>
     */
    public function handle(AnalyticsWindowData $window): Collection
    {
        return collect([
            [
                'id' => 'page-views',
                'label' => __('capell-analytics::widgets.page_views'),
                'value' => $this->countEvents($window, AnalyticsEventType::PageView),
            ],
            [
                'id' => 'unique-visits',
                'label' => __('capell-analytics::widgets.unique_visits'),
                'value' => $this->countUniqueVisits($window),
            ],
            [
                'id' => 'clicks',
                'label' => __('capell-analytics::widgets.clicks'),
                'value' => $this->countEvents($window, AnalyticsEventType::Click),
            ],
        ]);
    }

    private function countEvents(AnalyticsWindowData $window, AnalyticsEventType $type): int
    {
        return AnalyticsEvent::query()
            ->where('type', $type)
            ->whereBetween('occurred_at', [$window->startsAt, $window->endsAt])
            ->when($window->siteId !== null, fn (Builder $builder): Builder => $builder->where('site_id', $window->siteId))
            ->when($window->languageId !== null, fn (Builder $builder): Builder => $builder->where('language_id', $window->languageId))
            ->count();
    }

    private function countUniqueVisits(AnalyticsWindowData $window): int
    {
        return AnalyticsEvent::query()
            ->whereBetween('occurred_at', [$window->startsAt, $window->endsAt])
            ->when($window->siteId !== null, fn (Builder $builder): Builder => $builder->where('site_id', $window->siteId))
            ->when($window->languageId !== null, fn (Builder $builder): Builder => $builder->where('language_id', $window->languageId))
            ->whereNotNull('visit_id')
            ->distinct('visit_id')
            ->count('visit_id');
    }
}
