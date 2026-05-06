<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\Pages\Actions;

use Capell\PublishingStudio\Actions\DiscardPublishingStudioAction;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User;
use Override;

class DiscardDraftsBulkAction extends BulkAction
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::bulk_actions.discard_drafts'))
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->tooltip(__('capell-admin::bulk_actions.discard_drafts_tooltip'))
            ->requiresConfirmation()
            ->modalDescription(fn (Collection $records): string => __(
                'capell-admin::bulk_actions.discard_drafts_confirm',
                ['count' => $records->count()],
            ))
            ->action(function (Collection $records): void {
                /** @var User $actor */
                $actor = auth()->user();

                $result = DiscardPublishingStudioAction::run($records, $actor);

                if ($result['discarded'] === 0) {
                    Notification::make()
                        ->title(__('capell-admin::bulk_actions.discard_drafts_none_discarded'))
                        ->body(__('capell-admin::bulk_actions.discard_drafts_none_discarded_body'))
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__('capell-admin::bulk_actions.discard_drafts_success', [
                        'count' => $result['discarded'],
                        'skipped' => $result['skipped'],
                    ]))
                    ->success()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'bulk-discard-drafts';
    }
}
