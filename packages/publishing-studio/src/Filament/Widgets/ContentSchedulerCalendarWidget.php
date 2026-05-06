<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Widgets;

use Capell\PublishingStudio\Actions\DashboardReports\BuildContentSchedulerEventsAction;
use Capell\PublishingStudio\Data\SchedulerEventData;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

final class ContentSchedulerCalendarWidget extends Widget
{
    protected string $view = 'capell-publishing-studio::widgets.content-scheduler-calendar';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return Collection<string, Collection<int, SchedulerEventData>>
     */
    #[Computed]
    public function eventsByDate(): Collection
    {
        return BuildContentSchedulerEventsAction::run()
            ->groupBy(fn (SchedulerEventData $event): string => $event->scheduledFor->format('Y-m-d'));
    }
}
