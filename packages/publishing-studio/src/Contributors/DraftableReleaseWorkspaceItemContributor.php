<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Contributors;

use Capell\PublishingStudio\Contracts\ReleaseWorkspaceItemContributor;
use Capell\PublishingStudio\Data\ReleaseWorkspaceItemData;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\WorkspaceRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class DraftableReleaseWorkspaceItemContributor implements ReleaseWorkspaceItemContributor
{
    /**
     * @return list<ReleaseWorkspaceItemData>
     */
    public function itemsFor(Workspace $workspace): array
    {
        $items = [];

        foreach (WorkspaceRegistry::all() as $modelClass => $registeredDraftable) {
            unset($registeredDraftable);

            $model = new $modelClass;

            if (! $model instanceof Model || ! $model->getConnection()->getSchemaBuilder()->hasTable($model->getTable())) {
                continue;
            }

            $modelClass::query()
                ->withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->orderBy($model->getKeyName())
                ->each(function (Model $record) use (&$items, $modelClass): void {
                    $items[] = new ReleaseWorkspaceItemData(
                        source: Str::headline(class_basename($modelClass)),
                        label: $this->labelFor($record),
                        modelClass: $modelClass,
                        modelId: $record->getKey(),
                        changeType: $this->changeTypeFor($record),
                        status: 'ready',
                        url: null,
                    );
                });
        }

        return $items;
    }

    private function labelFor(Model $record): string
    {
        foreach (['name', 'title', 'slug', 'key'] as $attribute) {
            $value = $record->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return sprintf('%s #%s', class_basename($record), (string) $record->getKey());
    }

    private function changeTypeFor(Model $record): string
    {
        return $record->getAttribute('shadowed_by_workspace_id') ? 'updated' : 'created';
    }
}
