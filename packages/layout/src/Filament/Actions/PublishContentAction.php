<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Actions;

use Capell\Admin\Actions\PublishRecordAction;
use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Core\Models\Page;
use Capell\Layout\Models\Content;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class PublishContentAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->color('success')
            ->label(__('capell-admin::button.publish'))
            ->color('success')
            ->button()
            ->modal()
            ->icon(Heroicon::CheckCircle)
            ->visible(fn (?Page $record): bool => $record?->isDraft() && ! $record?->trashed())
            ->requiresConfirmation()
            ->modalDescription(__('capell-admin::message.publish_draft_confirmation'))
            ->action($this->publishPage(...));
    }

    public static function getDefaultName(): ?string
    {
        return 'publish';
    }

    public function withSave(): self
    {
        return $this->label(__('capell-admin::button.save_publish_draft'))
            ->outlined(false)
            ->action(function (EditPage $livewire, ?Content $record): void {
                $livewire->save(shouldRedirect: false);

                $this->publishPage($record);
            });
    }

    private function publishPage(?Content $record): void
    {
        PublishRecordAction::run($record);

        Notification::make()
            ->title(
                __(
                    'capell-admin::message.resource_published',
                    ['name' => __('capell-layout::generic.content')]
                )
            )
            ->success()
            ->send();
    }
}
