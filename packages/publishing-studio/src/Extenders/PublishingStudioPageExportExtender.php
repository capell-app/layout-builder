<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Extenders;

use Capell\Admin\Contracts\Extenders\PageExportExtender;
use Capell\PublishingStudio\Models\Workspace;
use Filament\FormBuilder\Components\Select;
use Filament\Schemas\Components\Component;

class PublishingStudioPageExportExtender implements PageExportExtender
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
