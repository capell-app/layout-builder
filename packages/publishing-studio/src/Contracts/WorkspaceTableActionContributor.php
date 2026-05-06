<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Contracts;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

interface WorkspaceTableActionContributor
{
    public const TAG = 'capell.publishing-studio.table_action_contributors';

    /**
     * @return array<int, Action|ActionGroup>
     */
    public function actions(): array;
}
