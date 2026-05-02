<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Listeners;

use Capell\ThemeStudio\Admin\Actions\ActivateApprovedThemeDraftAction;
use Capell\Workspaces\Events\WorkspaceStateChanged;

class ActivateApprovedThemeDraft
{
    public function handle(WorkspaceStateChanged $event): void
    {
        if ($event->transition !== 'approved') {
            return;
        }

        ActivateApprovedThemeDraftAction::run((int) $event->workspace->getKey());
    }
}
