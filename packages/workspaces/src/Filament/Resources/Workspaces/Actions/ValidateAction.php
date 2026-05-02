<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Resources\Workspaces\Actions;

use Capell\Workspaces\DryRunReport;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Publisher;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Override;
use Throwable;

class ValidateAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::workspace.actions.validate'))
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('info')
            ->authorize('view')
            ->action(function (Workspace $record): void {
                $report = (new Publisher)->dryRun($record);

                $this->notifyFromReport($report);
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'validate';
    }

    private function notifyFromReport(DryRunReport $report): void
    {
        if ($report->failure instanceof Throwable) {
            Notification::make()
                ->title(__('capell-admin::workspace.notifications.validate_failed'))
                ->body($report->failure->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        if ($report->hasCollisions() || $report->hasConflicts()) {
            Notification::make()
                ->title(__('capell-admin::workspace.notifications.validate_warnings'))
                ->body(__('capell-admin::workspace.notifications.validate_warnings_body', [
                    'collisions' => count($report->collisions),
                    'conflicts' => $report->rebaseReport?->conflictCount() ?? 0,
                ]))
                ->warning()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title(__('capell-admin::workspace.notifications.validate_passed'))
            ->body(__('capell-admin::workspace.notifications.validate_passed_body', [
                'rows' => $report->totalRows(),
            ]))
            ->success()
            ->send();
    }
}
