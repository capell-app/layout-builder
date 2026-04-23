<?php

declare(strict_types=1);

namespace Capell\Workspaces\Services;

use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\WorkspaceRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Jfcherng\Diff\DiffHelper;

/**
 * Produces attribute-level diffs of every workspace-scoped record against
 * its corresponding live row. Intended for a reviewer UI that needs a
 * compact view of what changed inside a workspace before approval.
 *
 * Structural HTML/rich-text diffing is deliberately out of scope here —
 * callers that want side-by-side HTML rendering should post-process the
 * `before` / `after` strings returned for those attributes.
 */
class WorkspaceDiffService
{
    /**
     * @return Collection<int, array{
     *     model: class-string<Model>,
     *     uuid: ?string,
     *     workspace_id: int,
     *     live_id: ?int,
     *     kind: 'added' | 'modified' | 'deleted',
     *     changes: array<string, array{before: mixed, after: mixed}>,
     * }>
     */
    public function diff(Workspace $workspace): Collection
    {
        $diffs = collect();

        foreach (array_keys(WorkspaceRegistry::all()) as $modelClass) {
            $prototype = new $modelClass;

            if (! $this->modelSupportsDiff($prototype)) {
                continue;
            }

            $workspaceRows = $modelClass::query()
                ->withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->get();

            foreach ($workspaceRows as $workspaceRow) {
                $uuid = $this->attribute($workspaceRow, 'uuid');

                $liveRow = $uuid === null
                    ? null
                    : $modelClass::query()
                        ->withoutGlobalScopes()
                        ->where('workspace_id', 0)
                        ->where('uuid', $uuid)
                        ->first();

                $entry = $this->diffPair($modelClass, $workspaceRow, $liveRow);

                if ($entry !== null) {
                    $diffs->push($entry);
                }
            }
        }

        return $diffs;
    }

    /**
     * Like {@see self::diff()} but includes unchanged attributes so the
     * reviewer UI can offer a "show unchanged" toggle without running a
     * second query. Entries are shaped for a Livewire diff panel: each
     * attribute is tagged `changed` / `unchanged` / `added` / `removed`
     * with its before/after/value.
     *
     * @return Collection<int, array{
     *     model: class-string<Model>,
     *     uuid: ?string,
     *     workspace_id: int,
     *     live_id: ?int,
     *     kind: 'added' | 'modified' | 'deleted' | 'unchanged',
     *     attributes: array<string, array<string, mixed>>,
     * }>
     */
    public function diffTree(Workspace $workspace): Collection
    {
        $ignored = ['id', 'workspace_id', 'shadowed_by_workspace_id', 'created_at', 'updated_at', 'deleted_at'];
        $tree = collect();

        foreach (array_keys(WorkspaceRegistry::all()) as $modelClass) {
            $prototype = new $modelClass;
            if (! $this->modelSupportsDiff($prototype)) {
                continue;
            }

            $workspaceRows = $modelClass::query()
                ->withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->get();

            foreach ($workspaceRows as $workspaceRow) {
                $uuid = $this->attribute($workspaceRow, 'uuid');
                $liveRow = $uuid === null
                    ? null
                    : $modelClass::query()
                        ->withoutGlobalScopes()
                        ->where('workspace_id', 0)
                        ->where('uuid', $uuid)
                        ->first();

                if ($this->isTombstone($workspaceRow) && $liveRow instanceof Model) {
                    $attributes = [];
                    foreach ($liveRow->getAttributes() as $key => $value) {
                        if (in_array($key, $ignored, true)) {
                            continue;
                        }

                        $attributes[$key] = ['status' => 'removed', 'value' => $value];
                    }

                    $tree->push([
                        'model' => $modelClass,
                        'uuid' => $uuid,
                        'workspace_id' => (int) $workspaceRow->getAttribute('workspace_id'),
                        'live_id' => $liveRow->getKey() === null ? null : (int) $liveRow->getKey(),
                        'kind' => 'deleted',
                        'attributes' => $attributes,
                    ]);

                    continue;
                }

                $workspaceAttrs = $workspaceRow->getAttributes();
                $liveAttrs = $liveRow?->getAttributes() ?? [];
                $keys = array_values(array_unique(array_merge(array_keys($workspaceAttrs), array_keys($liveAttrs))));
                sort($keys);

                $attributes = [];
                $hasAnyChange = $liveRow === null;
                foreach ($keys as $key) {
                    if (in_array($key, $ignored, true)) {
                        continue;
                    }

                    $before = $liveAttrs[$key] ?? null;
                    $after = $workspaceAttrs[$key] ?? null;

                    if ($liveRow === null) {
                        $attributes[$key] = ['status' => 'added', 'value' => $after];

                        continue;
                    }

                    if ($before === $after) {
                        $attributes[$key] = ['status' => 'unchanged', 'value' => $before];

                        continue;
                    }

                    $attributes[$key] = ['status' => 'changed', 'before' => $before, 'after' => $after];
                    $hasAnyChange = true;
                }

                $tree->push([
                    'model' => $modelClass,
                    'uuid' => $uuid,
                    'workspace_id' => (int) $workspaceRow->getAttribute('workspace_id'),
                    'live_id' => $liveRow?->getKey() === null ? null : (int) $liveRow->getKey(),
                    'kind' => $liveRow === null ? 'added' : ($hasAnyChange ? 'modified' : 'unchanged'),
                    'attributes' => $attributes,
                ]);
            }
        }

        return $tree;
    }

    /**
     * Render a side-by-side HTML diff for the given before/after strings
     * using {@see DiffHelper}. Non-string scalars are stringified;
     * nulls are passed through as empty strings. The returned fragment is a
     * self-contained `<table class="diff ...">` block safe to echo inside a
     * blade view.
     */
    public function renderHtmlDiff(mixed $before, mixed $after): string
    {
        /** @phpstan-ignore-next-line class.notFound */
        return DiffHelper::calculate(
            $this->stringify($before),
            $this->stringify($after),
            'SideBySide',
            [],
            [
                'detailLevel' => 'word',
                'showHeader' => false,
                'spacesToNbsp' => false,
            ],
        );
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array{
     *     model: class-string<Model>,
     *     uuid: ?string,
     *     workspace_id: int,
     *     live_id: ?int,
     *     kind: 'added' | 'modified' | 'deleted',
     *     changes: array<string, array{before: mixed, after: mixed}>,
     * }|null
     */
    private function diffPair(string $modelClass, Model $workspaceRow, ?Model $liveRow): ?array
    {
        $ignored = ['id', 'workspace_id', 'shadowed_by_workspace_id', 'created_at', 'updated_at', 'deleted_at'];
        $kind = $liveRow instanceof Model ? 'modified' : 'added';

        $changes = [];

        if ($liveRow instanceof Model) {
            $liveAttrs = $liveRow->getAttributes();
            $workspaceAttrs = $workspaceRow->getAttributes();
            $keys = array_unique(array_merge(array_keys($liveAttrs), array_keys($workspaceAttrs)));

            foreach ($keys as $key) {
                if (in_array($key, $ignored, true)) {
                    continue;
                }

                $before = $liveAttrs[$key] ?? null;
                $after = $workspaceAttrs[$key] ?? null;

                if ($before !== $after) {
                    $changes[$key] = ['before' => $before, 'after' => $after];
                }
            }

            if ($changes === []) {
                return null;
            }
        } else {
            foreach ($workspaceRow->getAttributes() as $key => $value) {
                if (in_array($key, $ignored, true)) {
                    continue;
                }

                $changes[$key] = ['before' => null, 'after' => $value];
            }
        }

        return [
            'model' => $modelClass,
            'uuid' => $this->attribute($workspaceRow, 'uuid'),
            'workspace_id' => (int) $workspaceRow->getAttribute('workspace_id'),
            'live_id' => $liveRow?->getKey() === null ? null : (int) $liveRow->getKey(),
            'kind' => $kind,
            'changes' => $changes,
        ];
    }

    private function isTombstone(Model $row): bool
    {
        $attributes = $row->getAttributes();

        return array_key_exists('deleted_at', $attributes) && $attributes['deleted_at'] !== null;
    }

    private function modelSupportsDiff(Model $prototype): bool
    {
        return array_key_exists('workspace_id', $prototype->getAttributes())
            || in_array('workspace_id', $prototype->getFillable(), true)
            || Schema::hasColumn($prototype->getTable(), 'workspace_id');
    }

    private function attribute(Model $model, string $key): ?string
    {
        $value = $model->getAttribute($key);

        return $value === null ? null : (string) $value;
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
