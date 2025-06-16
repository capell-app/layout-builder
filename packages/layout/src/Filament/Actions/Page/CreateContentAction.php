<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Actions\Page;

use Capell\Admin\Enums\ModalWidthEnum;
use Capell\Layout\Actions\MutateContentDataBeforeCreateAction;
use Capell\Layout\Models\Content;
use Filament\Actions\CreateAction;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;

class CreateContentAction extends CreateAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->model(Content::class)
            ->url(null)
            ->modal()
            ->slideOver()
            ->form(
                fn (Form $form): Form => $form
                    ->operation('createOption')
                    ->schema(fn ($livewire): array => $livewire->getResource()::getFormSchema($form))
            )
            ->modalWidth(ModalWidthEnum::Default->value)
            ->groupedIcon('heroicon-m-plus-circle')
            ->successRedirectUrl(
                fn ($livewire, Content $record): string => $livewire->getResource()::getUrl('edit', [$record])
            )
            ->fillForm(
                data: function (Page $livewire): array {
                    $form = $livewire->getMountedActionForm();
                    $form->fill();

                    $data = $form->getRawState();

                    return $this->mutateFormData($data);
                }
            );
    }

    private function mutateFormData(array $data): array
    {
        return MutateContentDataBeforeCreateAction::run($data);
    }
}
