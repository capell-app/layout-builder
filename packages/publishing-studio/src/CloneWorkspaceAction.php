<?php

declare(strict_types=1);

namespace Capell\PublishingStudio;

use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Events\WorkspaceEventDispatcher;
use Capell\PublishingStudio\Models\Workspace;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Spin up a new workspace pre-populated from another. Draftable rows scoped
 * to the source workspace are replicated under the new workspace id so the
 * new workspace behaves like a fresh checkout of the source: editors can
 * modify or publish it without affecting the source.
 *
 * Clone preserves the source rows' `uuid` values so that, on publish, the
 * existing live-row matching logic still works. The clone is always created
 * in Open status regardless of the source's state.
 */
class CloneWorkspaceAction
{
    public function __construct(private readonly WorkspaceRegistry $registry = new WorkspaceRegistry) {}

    public function clone(
        Workspace $source,
        CloneOptions $options = new CloneOptions,
        ?Authenticatable $actor = null,
    ): Workspace {
        return DB::transaction(function () use ($source, $options, $actor): Workspace {
            $clone = new Workspace;
            $clone->name = $options->newName ?? $source->name . ' (copy)';
            $clone->slug = $options->newSlug ?? Str::slug($clone->name) . '-' . Str::lower(Str::random(6));
            $clone->description = $options->description ?? $source->description;
            $clone->color = $source->color;
            $clone->status = WorkspaceStatusEnum::Open;
            $clone->base_version_id = $source->base_version_id;
            $clone->cloned_from_id = $source->id;
            $clone->settings = $options->copySettings ? $source->settings : null;

            if ($actor instanceof Authenticatable) {
                $clone->created_by = $actor->getAuthIdentifier();
                $clone->updated_by = $actor->getAuthIdentifier();
            }

            $clone->save();

            /** @var WorkspaceEventDispatcher $dispatcher */
            $dispatcher = resolve(WorkspaceEventDispatcher::class);

            // Dispatch beforeClone event
            throw_unless($dispatcher->beforeClone($source, $clone), Exception::class, 'Clone prevented by subscriber');

            if ($options->copyDrafts) {
                $this->copyDraftableRows($source, $clone);
            }

            // Dispatch afterClone event
            $dispatcher->afterClone($source, $clone);

            return $clone->refresh();
        });
    }

    private function copyDraftableRows(Workspace $source, Workspace $clone): void
    {
        foreach (array_keys($this->registry::all()) as $modelClass) {
            $this->copyRowsFor($modelClass, $source, $clone);
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function copyRowsFor(string $modelClass, Workspace $source, Workspace $clone): void
    {
        $rows = $modelClass::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $source->id)
            ->when($this->usesSoftDeletes(new $modelClass), fn (Builder $query): Builder => $query->whereNull('deleted_at'))
            ->get();

        foreach ($rows as $row) {
            /** @var Model $row */
            $replica = $row->replicate();
            $replica->setAttribute('workspace_id', $clone->id);
            $replica->setAttribute('shadowed_by_workspace_id', 0);
            $replica->save();
        }
    }

    private function usesSoftDeletes(Model $record): bool
    {
        $traitNames = array_map(
            static fn (string $fqcn): string => ltrim($fqcn, '\\'),
            class_uses_recursive($record),
        );

        return in_array(SoftDeletes::class, $traitNames, true);
    }
}
