<?php

declare(strict_types=1);

namespace Capell\Workspaces\Extenders;

use Capell\Admin\Contracts\Extenders\PageExportExtender;
use Capell\Workspaces\Models\Workspace;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;

class WorkspacesPageExportExtender implements PageExportExtender
{
    /** @return array<int, Component> */
    public function getFormFields(): array
    {
        return [
            Select::make('source_workspace_id')
                ->label(__('capell-admin::exchanger.export.source_workspace'))
                ->options(fn (): array => Workspace::query()->pluck('name', 'id')->all())
                ->placeholder(__('capell-admin::exchanger.export.source_live'))
                ->native(false),
        ];
    }

    /** @return array<string, mixed> */
    public function resolveOptions(array $data): array
    {
        $workspaceId = $data['source_workspace_id'] ?? null;

        return [
            'source_workspace' => $workspaceId === null ? null : Workspace::query()->find($workspaceId),
        ];
    }
}
