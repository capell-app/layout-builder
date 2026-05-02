<?php

declare(strict_types=1);

namespace Capell\Workspaces\Activity;

use Capell\Workspaces\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Read-model over the spatie/laravel-activitylog `activity_log` table that
 * surfaces recent workspace state transitions for the Filament dashboard
 * widget. Joins each log row back to a {@see Workspace} so the widget can
 * link directly to the compare page.
 */
class WorkspaceActivityFeed
{
    /**
     * @return Collection<int, WorkspaceActivityEntry>
     */
    public function recent(int $limit = 20): Collection
    {
        $activityClass = config('activitylog.activity_model', Activity::class);

        $rows = $activityClass::query()
            ->where('subject_type', (new Workspace)->getMorphClass())
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $workspaceIds = $rows->pluck('subject_id')->filter()->unique()->values()->all();
        $workspacesById = Workspace::query()
            ->whereIn('id', $workspaceIds)
            ->get()
            ->keyBy('id');

        return $rows->map(function (Activity $row) use ($workspacesById): WorkspaceActivityEntry {
            $workspace = $workspacesById->get($row->subject_id);

            return new WorkspaceActivityEntry(
                workspaceId: (int) $row->subject_id,
                workspaceName: $workspace instanceof Workspace ? $workspace->name : null,
                description: $row->description,
                event: $row->event,
                causerId: $row->causer_id,
                causerType: $row->causer_type,
                occurredAt: CarbonImmutable::instance($row->created_at),
            );
        })->values();
    }
}
