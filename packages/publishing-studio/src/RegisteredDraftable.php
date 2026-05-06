<?php

declare(strict_types=1);

namespace Capell\PublishingStudio;

use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Database\Eloquent\Model;

/**
 * Value object describing a single model's participation in the workspace
 * system. Exposed through {@see WorkspaceRegistry::get()}.
 *
 * @param  class-string<Model>  $modelClass
 */
final readonly class RegisteredDraftable
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  null|callable(Model, Workspace): Model  $cloneUsing
     * @param  null|callable(Model): Model  $finalizeOnPublish
     */
    public function __construct(
        public string $modelClass,
        private mixed $cloneUsing,
        private mixed $finalizeOnPublish,
    ) {}

    /**
     * Clone a live record into the given workspace, returning the cloned
     * workspace-scoped row. Callers are responsible for persisting the
     * returned model (the callback may choose to save or leave it dirty).
     */
    public function cloneInto(Model $source, Workspace $workspace): Model
    {
        if ($this->cloneUsing !== null) {
            return ($this->cloneUsing)($source, $workspace);
        }

        return $this->defaultClone($source, $workspace);
    }

    /**
     * Hook that runs against a workspace-scoped record just before publish
     * flips its `workspace_id` back to 0. A place for packages to rebuild
     * caches, regenerate slugs, or notify listeners.
     */
    public function finalizeOnPublish(Model $record): Model
    {
        if ($this->finalizeOnPublish !== null) {
            return ($this->finalizeOnPublish)($record);
        }

        return $record;
    }

    private function defaultClone(Model $source, Workspace $workspace): Model
    {
        $clone = $source->replicate();
        $clone->setAttribute('workspace_id', $workspace->id);

        return $clone;
    }
}
