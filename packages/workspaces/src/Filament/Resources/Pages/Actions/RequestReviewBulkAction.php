<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Resources\Pages\Actions;

use Capell\Workspaces\Actions\RequestReviewBulkAction as RequestReviewAction;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User;
use Override;

class RequestReviewBulkAction extends BulkAction
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::bulk_actions.request_review'))
            ->icon(Heroicon::OutlinedEye)
            ->tooltip(__('capell-admin::bulk_actions.request_review_tooltip'))
            ->requiresConfirmation()
            ->action(function (Collection $records): void {
                /** @var User $actor */
                $actor = auth()->user();

                $result = RequestReviewAction::run($records, $actor);

                Notification::make()
                    ->title(__('capell-admin::bulk_actions.request_review_success', [
                        'count' => $result['requested'],
                        'skipped' => $result['skipped'],
                    ]))
                    ->success()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'bulk-request-review';
    }
}
