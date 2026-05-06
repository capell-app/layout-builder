<?php

declare(strict_types=1);

namespace Capell\Tests\Fixtures\Concerns;

use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Publisher;
use Capell\PublishingStudio\WorkspaceContext;
use Capell\PublishingStudio\WorkspaceRegistry;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

use RuntimeException;

trait HasAssertWorkspaceDraftable
{
    /**
     * Reusable contract assertion for any model using the BelongsToWorkspace
     * trait. Downstream packages invoke this from a Pest test to prove their
     * draftable implementation honours the copy-on-write, publish, and delete
     * behaviour that the core engine assumes.
     *
     * The factory must return a freshly-persisted row of $modelClass. When a
     * Workspace is passed, the row should be stamped into that workspace;
     * otherwise it must land on live.
     *
     * @param  class-string<Model>  $modelClass
     * @param  Closure(Workspace|null): Model  $factory
     */
    protected function assertWorkspaceDraftableContract(string $modelClass, Closure $factory): void
    {
        if (! WorkspaceRegistry::isRegistered($modelClass)) {
            WorkspaceRegistry::register($modelClass);
        }

        WorkspaceContext::set(null);

        $liveRow = $factory(null);
        assertSame(0, (int) $liveRow->getAttribute('workspace_id'), 'Factory must create live rows with workspace_id = 0 when no workspace is passed.');
        assertTrue($liveRow->exists, 'Factory must persist the row before returning it.');
        assertNotNull($liveRow->getKey());

        $workspace = Workspace::factory()->open()->create();
        WorkspaceContext::set($workspace);

        try {
            $liveRow->refresh();

            $editable = $liveRow;
            $dirtied = $this->contractMutateForEdit($editable);
            $editable->save();

            $liveAfterEdit = $modelClass::query()
                ->withoutGlobalScopes()
                ->where('workspace_id', 0)
                ->whereKey($liveRow->getKey())
                ->first();

            assertNotNull($liveAfterEdit, 'Live row must still exist after an in-workspace edit.');

            foreach ($dirtied as $attribute => $originalValue) {
                assertSame(
                    $originalValue,
                    $liveAfterEdit->getAttribute($attribute),
                    sprintf('Copy-on-write must leave live attribute %s untouched.', $attribute),
                );
            }

            $shadowRow = $modelClass::query()
                ->withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->first();

            assertNotNull($shadowRow, 'A workspace-scoped shadow row must be created when a live row is edited.');
        } finally {
            WorkspaceContext::set(null);
        }

        $workspace->status = WorkspaceStatusEnum::Approved;
        $workspace->save();

        $publisher = new Publisher;
        $publisher->publish($workspace);

        $flippedRow = $modelClass::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', 0)
            ->where('uuid', $liveRow->getAttribute('uuid'))
            ->first();

        assertNotNull($flippedRow, 'Publish must flip the workspace shadow row to live.');

        $deletionTargetWorkspace = Workspace::factory()->open()->create();
        WorkspaceContext::set($deletionTargetWorkspace);

        try {
            $liveForDelete = $modelClass::query()
                ->withoutGlobalScopes()
                ->where('workspace_id', 0)
                ->whereKey($flippedRow->getKey())
                ->first();

            assertNotNull($liveForDelete, 'Live row must exist before the deletion test.');

            $traits = array_map(
                static fn (string $fqcn): string => ltrim($fqcn, '\\'),
                class_uses_recursive($liveForDelete),
            );

            if (! in_array(SoftDeletes::class, $traits, true)) {
                // Hard-delete path is not allowed for live rows inside a workspace
                // context; a well-behaved draftable throws LogicException. We
                // stop here so draftables without SoftDeletes still pass the
                // earlier assertions.
                return;
            }

            $liveForDelete->delete();

            $stillLive = $modelClass::query()
                ->withoutGlobalScopes()
                ->where('workspace_id', 0)
                ->whereKey($flippedRow->getKey())
                ->whereNull('deleted_at')
                ->first();

            assertNotNull($stillLive, 'Live row must remain intact when deleted inside a workspace (tombstone lives in the workspace).');

            $tombstone = $modelClass::query()
                ->withoutGlobalScopes()
                ->where('workspace_id', $deletionTargetWorkspace->id)
                ->whereNotNull('deleted_at')
                ->first();

            assertNotNull($tombstone, 'Deleting a live row inside a workspace must create a soft-deleted tombstone in the workspace.');
        } finally {
            WorkspaceContext::set(null);
        }
    }

    /**
     * Best-effort mutation: pick any writable string column on the row and
     * dirty it for copy-on-write testing. Returns a map of
     * `attribute => original value` so assertions can verify live wasn't
     * touched.
     *
     * @return array<string, mixed>
     */
    protected function contractMutateForEdit(Model $record): array
    {
        $candidates = ['name', 'title', 'slug', 'label'];

        foreach ($candidates as $candidateAttribute) {
            if ($record->getAttribute($candidateAttribute) !== null) {
                $original = $record->getAttribute($candidateAttribute);
                $record->setAttribute($candidateAttribute, $original . '-edited');

                return [$candidateAttribute => $original];
            }
        }

        foreach ($record->getAttributes() as $attribute => $value) {
            if (in_array($attribute, ['id', 'uuid', 'workspace_id', 'shadowed_by_workspace_id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }

            if (is_string($value)) {
                $record->setAttribute($attribute, $value . '-edited');

                return [$attribute => $value];
            }
        }

        throw new RuntimeException('Could not find a writable string column to mutate on ' . $record::class);
    }
}
