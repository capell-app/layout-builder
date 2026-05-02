<?php

declare(strict_types=1);

namespace Capell\Workspaces\Contracts;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

interface WorkspaceTableActionContributor
{
    public const TAG = 'capell.workspaces.table_action_contributors';

    /**
     * @return array<int, Action|ActionGroup>
     */
    public function actions(): array;
}
