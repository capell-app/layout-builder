<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Actions;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Models\Contracts\Draftable;
use Capell\Layout\Enums\ResourceEnum;
use Capell\Layout\Filament\Resources\Contents\ContentResource;
use Filament\Actions\Action;
use Filament\Support\Enums\Size;

class DeleteDraftContentAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::button.delete_draft'))
            ->color('warning')
            ->size(Size::Small)
            ->link()
            ->modal()
            ->requiresConfirmation()
            ->modalDescription(__('capell-admin::message.delete_draft_confirmation'))
            ->action(function (Draftable $record): void {
                /** @var class-string<ContentResource> $resource */
                $resource = CapellAdmin::getResource(ResourceEnum::Content);

                if ($record->revisions()->count() === 1) {
                    $record->delete();

                    $this->redirect($resource::getUrl('index'));

                    return;
                }

                $published = $record->revisions()->published()->first();

                $published->updateQuietly([
                    'is_current' => true,
                ]);

                $record->delete();

                $this->redirect($resource::getUrl('edit', ['record' => $published]));
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'deleteDraft';
    }
}
