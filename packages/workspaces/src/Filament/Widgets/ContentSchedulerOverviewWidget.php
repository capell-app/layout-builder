<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Workspaces\Actions\Reports\BuildContentSchedulerEventsAction;
use Capell\Workspaces\Data\SchedulerEventData;
use Capell\Workspaces\Enums\SchedulerEventTypeEnum;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

final class ContentSchedulerOverviewWidget extends StatsOverviewWidget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'content_scheduler';

    protected ?string $heading = null;

    protected int|array|null $columns = [
        'default' => 2,
        'md' => 4,
    ];

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $events = BuildContentSchedulerEventsAction::run();

        return [
            $this->stat($events, SchedulerEventTypeEnum::Publish),
            $this->stat($events, SchedulerEventTypeEnum::Unpublish),
            $this->stat($events, SchedulerEventTypeEnum::Embargo),
            $this->stat($events, SchedulerEventTypeEnum::ReviewReminder),
        ];
    }

    /**
     * @param  Collection<int, mixed>  $events
     */
    private function stat(Collection $events, SchedulerEventTypeEnum $eventType): Stat
    {
        return Stat::make(
            $eventType->getLabel(),
            $events->filter(fn (SchedulerEventData $event): bool => $event->eventType === $eventType)->count(),
        )
            ->color($eventType->getColor());
    }
}
