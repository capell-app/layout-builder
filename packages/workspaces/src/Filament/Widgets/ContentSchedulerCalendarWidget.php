<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Widgets;

use Capell\Workspaces\Actions\Reports\BuildContentSchedulerEventsAction;
use Capell\Workspaces\Data\SchedulerEventData;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

final class ContentSchedulerCalendarWidget extends Widget
{
    protected string $view = 'capell-workspaces::widgets.content-scheduler-calendar';

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
