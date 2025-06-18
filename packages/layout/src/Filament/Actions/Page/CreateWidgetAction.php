<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Actions\Page;

use Capell\Admin\Enums\ModalWidthEnum;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Models\Widget;
use Filament\Actions\CreateAction;
use Filament\Forms\Form;

class CreateWidgetAction extends CreateAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->model(Widget::class)
            ->url(null)
            ->modal()
            ->slideOver()
            ->fillForm(function ($livewire): array {
                $form = $livewire->getMountedActionForm();
                $form->fill();

                $data = $form->getRawState();

                $data['type_id'] = Type::query()->where('type', LayoutTypeEnum::Widget)->default()->value('id');

                $data['status'] = true;

                return $data;
            })
            ->form(
                fn (Form $form): Form => $form
                    ->operation('createOption')
                    ->schema(fn ($livewire): array => $livewire->getResource()::getFormSchema($form))
            )
            ->modalWidth(ModalWidthEnum::Default->value)
            ->groupedIcon('heroicon-m-plus-circle')
            ->successRedirectUrl(
                fn ($livewire, Widget $record): string => $livewire->getResource()::getUrl('edit', [$record])
            );
    }
}
