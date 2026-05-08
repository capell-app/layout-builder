<?php

declare(strict_types=1);

namespace Capell\Events\Filament\Widgets;

use Capell\Events\Models\EventOccurrence;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class EventCalendarWidget extends Widget
{
    protected string $view = 'capell-events::filament.widgets.event-calendar';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return Collection<string, Collection<int, EventOccurrence>>
     */
    #[Computed]
    public function occurrencesByDate(): Collection
    {
        $start = CarbonImmutable::now()->startOfDay();
        $end = $start->addDays(90)->endOfDay();

        return EventOccurrence::query()
            ->with(['event.translation', 'venue'])
            ->whereBetween('starts_at', [$start, $end])
            ->oldest('starts_at')
            ->limit(250)
            ->get()
            ->groupBy(fn (EventOccurrence $occurrence): string => $occurrence->starts_at->toDateString());
    }
}
