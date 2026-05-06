<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Console\Commands;

use Capell\PublishingStudio\Actions\CopyOnWriteAction;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\WorkspaceContext;
use Capell\PublishingStudio\WorkspaceRegistry;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Prunes abandoned publishing-studio. For each workspace this walks every registered
 * draftable model, clears the `shadowed_by_workspace_id` flag on the matching
 * live rows, deletes the workspace-scoped rows, then removes the workspace
 * itself. The whole operation runs in a single transaction so a failure
 * leaves the database exactly as it was before the command started.
 */
class PruneAbandonedPublishingStudioCommand extends Command
{
    /** @var string */
    protected $description = 'Delete abandoned publishing-studio and release any live rows they were shadowing.';

    /** @var string */
    protected $signature = 'capell:publishing-studio:prune
        {--id=* : Prune a specific workspace id instead of every abandoned workspace}
        {--dry-run : Report what would be pruned without making changes}';

    public function handle(CopyOnWriteAction $copyOnWrite): int
    {
        $publishingStudio = $this->targetPublishingStudio();

        if ($publishingStudio->isEmpty()) {
            $this->info('No publishing-studio to prune.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $prunedCount = 0;

        foreach ($publishingStudio as $workspace) {
            $summary = $this->summariseWorkspace($workspace);
            $this->line(sprintf(
                ' - #%d "%s" — %s',
                $workspace->id,
                $workspace->name,
                $summary,
            ));

            if ($dryRun) {
                continue;
            }

            $this->pruneWorkspace($workspace, $copyOnWrite);
            $prunedCount++;
        }

        $this->info(
            $dryRun
            ? sprintf('Dry run: %d workspace(s) would be pruned.', $publishingStudio->count())
            : sprintf('Pruned %d workspace(s).', $prunedCount),
        );

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Workspace>
     */
    private function targetPublishingStudio(): Collection
    {
        /** @var array<int, int|string> $explicitIds */
        $explicitIds = $this->option('id') ?? [];

        $query = Workspace::query();

        if ($explicitIds !== []) {
            $query->whereIn('id', array_map(intval(...), $explicitIds));
        } else {
            $query->where('status', WorkspaceStatusEnum::Abandoned->value);
        }

        return $query->get();
    }

    private function summariseWorkspace(Workspace $workspace): string
    {
        $counts = [];

        foreach (array_keys(WorkspaceRegistry::all()) as $modelClass) {
            $count = $modelClass::query()
                ->withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->count();

            if ($count > 0) {
                $counts[] = sprintf('%d %s', $count, class_basename($modelClass));
            }
        }

        return $counts === [] ? 'no draft rows' : implode(', ', $counts);
    }

    private function pruneWorkspace(Workspace $workspace, CopyOnWriteAction $copyOnWrite): void
    {
        DB::transaction(function () use ($workspace, $copyOnWrite): void {
            WorkspaceContext::clear();

            foreach (array_keys(WorkspaceRegistry::all()) as $modelClass) {
                /** @var Model $prototype */
                $prototype = new $modelClass;
                $keyName = $prototype->getKeyName();
                $table = $prototype->getTable();

                if (! DB::getSchemaBuilder()->hasColumn($table, 'shadowed_by_workspace_id')) {
                    $modelClass::query()
                        ->withoutGlobalScopes()
                        ->where('workspace_id', $workspace->id)
                        ->delete();

                    continue;
                }

                $shadowedLiveRows = $modelClass::query()
                    ->withoutGlobalScopes()
                    ->where('workspace_id', 0)
                    ->where('shadowed_by_workspace_id', $workspace->id)
                    ->get();

                foreach ($shadowedLiveRows as $shadowedLive) {
                    $copyOnWrite->clearShadow($shadowedLive, $workspace);
                }

                $modelClass::query()
                    ->withoutGlobalScopes()
                    ->where('workspace_id', $workspace->id)
                    ->get([$keyName])
                    ->each(function (Model $draft) use ($modelClass, $keyName): void {
                        $modelClass::query()
                            ->withoutGlobalScopes()
                            ->where($keyName, $draft->getKey())
                            ->delete();
                    });
            }

            $workspace->forceDelete();
        });
    }
}
