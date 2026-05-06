<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions\DashboardReports;

use Capell\Core\Actions\GetEditPageResourceUrlAction;
use Capell\Core\Models\Page;
use Capell\PublishingStudio\Data\SchedulerEventData;
use Capell\PublishingStudio\Enums\SchedulerEventTypeEnum;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\WorkspaceResource;
use Capell\PublishingStudio\Models\Workspace;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildContentSchedulerEventsAction
{
    use AsAction;

    /**
     * @return Collection<int, SchedulerEventData>
     */
    public function handle(
        ?SchedulerEventTypeEnum $eventType = null,
        ?string $sourceType = null,
        ?CarbonInterface $startsAt = null,
        ?CarbonInterface $endsAt = null,
    ): Collection {
        $events = collect();

        if ($sourceType === null || $sourceType === 'page') {
            $events = $events->merge($this->pageEvents());
        }

        if ($sourceType === null || $sourceType === 'workspace') {
            $events = $events->merge($this->workspaceEvents());
        }

        return $events
            ->filter(fn (SchedulerEventData $event): bool => ! $eventType instanceof SchedulerEventTypeEnum || $event->eventType === $eventType)
            ->filter(fn (SchedulerEventData $event): bool => ! $startsAt instanceof CarbonInterface || $event->scheduledFor->greaterThanOrEqualTo($startsAt))
            ->filter(fn (SchedulerEventData $event): bool => ! $endsAt instanceof CarbonInterface || $event->scheduledFor->lessThanOrEqualTo($endsAt))
            ->sortBy(fn (SchedulerEventData $event): int => $event->scheduledFor->getTimestamp())
            ->values();
    }

    /**
     * @return Collection<int, SchedulerEventData>
     */
    private function pageEvents(): Collection
    {
        return Page::query()
            ->with('type')
            ->where(function (Builder $query): void {
                $query->where('visible_from', '>', now())
                    ->orWhere('visible_until', '>', now());
            })
            ->get()
            ->flatMap(function (Page $page): array {
                $events = [];
                $page->loadMissing('type');
                $recordUrl = GetEditPageResourceUrlAction::run($page);

                if ($page->visible_from !== null && $page->visible_from->isFuture()) {
                    $events[] = new SchedulerEventData(
                        id: 'page-' . $page->id . '-publish',
                        sourceType: 'page',
                        sourceId: $page->id,
                        title: $page->name,
                        eventType: SchedulerEventTypeEnum::Publish,
                        scheduledFor: $page->visible_from,
                        status: (string) __('capell-publishing-studio::scheduler.status.page_scheduled'),
                        description: (string) __('capell-publishing-studio::scheduler.descriptions.page_publish'),
                        recordUrl: $recordUrl,
                    );
                }

                if ($page->visible_until !== null && $page->visible_until->isFuture()) {
                    $events[] = new SchedulerEventData(
                        id: 'page-' . $page->id . '-unpublish',
                        sourceType: 'page',
                        sourceId: $page->id,
                        title: $page->name,
                        eventType: SchedulerEventTypeEnum::Unpublish,
                        scheduledFor: $page->visible_until,
                        status: (string) __('capell-publishing-studio::scheduler.status.page_scheduled'),
                        description: (string) __('capell-publishing-studio::scheduler.descriptions.page_unpublish'),
                        recordUrl: $recordUrl,
                    );
                }

                return $events;
            });
    }

    /**
     * @return Collection<int, SchedulerEventData>
     */
    private function workspaceEvents(): Collection
    {
        $schedulerColumns = $this->workspaceSchedulerColumns();

        if ($schedulerColumns === []) {
            return collect();
        }

        return Workspace::query()
            ->where(function (Builder $query) use ($schedulerColumns): void {
                foreach (array_values($schedulerColumns) as $index => $column) {
                    if ($index === 0) {
                        $query->where($column, '>', now());

                        continue;
                    }

                    $query->orWhere($column, '>', now());
                }
            })
            ->get()
            ->flatMap(function (Workspace $workspace) use ($schedulerColumns): array {
                $events = [];
                $recordUrl = Route::has('filament.admin.resources.publishing-studio.index')
                    ? WorkspaceResource::getUrl('index', [
                        'tableSearch' => $workspace->name,
                    ])
                    : '#';

                if (in_array('publish_at', $schedulerColumns, true) && $workspace->publish_at !== null && $workspace->publish_at->isFuture()) {
                    $events[] = $this->workspaceEvent($workspace, SchedulerEventTypeEnum::Publish, $workspace->publish_at, 'workspace_publish', $recordUrl);
                }

                if (in_array('unpublish_at', $schedulerColumns, true) && $workspace->unpublish_at !== null && $workspace->unpublish_at->isFuture()) {
                    $events[] = $this->workspaceEvent($workspace, SchedulerEventTypeEnum::Unpublish, $workspace->unpublish_at, 'workspace_takedown_reminder', $recordUrl);
                }

                if (in_array('embargo_until', $schedulerColumns, true) && $workspace->embargo_until !== null && $workspace->embargo_until->isFuture()) {
                    $events[] = $this->workspaceEvent($workspace, SchedulerEventTypeEnum::Embargo, $workspace->embargo_until, 'workspace_embargo', $recordUrl);
                }

                if (in_array('review_reminder_at', $schedulerColumns, true) && $workspace->review_reminder_at !== null && $workspace->review_reminder_at->isFuture()) {
                    $events[] = $this->workspaceEvent($workspace, SchedulerEventTypeEnum::ReviewReminder, $workspace->review_reminder_at, 'workspace_review_reminder', $recordUrl);
                }

                return $events;
            });
    }

    /**
     * @return array<int, string>
     */
    private function workspaceSchedulerColumns(): array
    {
        $columns = Schema::getColumnListing((new Workspace)->getTable());

        return array_values(array_intersect([
            'publish_at',
            'unpublish_at',
            'embargo_until',
            'review_reminder_at',
        ], $columns));
    }

    private function workspaceEvent(
        Workspace $workspace,
        SchedulerEventTypeEnum $eventType,
        CarbonInterface $scheduledFor,
        string $descriptionKey,
        ?string $recordUrl,
    ): SchedulerEventData {
        return new SchedulerEventData(
            id: 'workspace-' . $workspace->id . '-' . $eventType->value,
            sourceType: 'workspace',
            sourceId: $workspace->id,
            title: $workspace->name,
            eventType: $eventType,
            scheduledFor: $scheduledFor,
            status: $workspace->status->getLabel(),
            description: (string) __('capell-publishing-studio::scheduler.descriptions.' . $descriptionKey),
            recordUrl: $recordUrl,
        );
    }
}
