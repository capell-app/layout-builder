<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Admin\Enums\ModalWidthEnum;
use Capell\Admin\Filament\Concerns\HasCustomSelectOption;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Filament\Resources\WidgetResource;
use Capell\Layout\Models\Widget;
use Filament\Forms;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class WidgetSelect extends Select
{
    use HasCustomSelectOption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::form.select_widget'));
    }

    public function withCreateForm(): self
    {
        return $this->model(CapellCore::getModel(LayoutModelEnum::Widget->name))
            ->getOptionLabelFromRecordUsing(
                fn (Widget $record): string => static::getSelectOption($record)
            )
            ->getOptionLabelUsing(fn (Select $component, $value): ?string => $component->getModel()::find($value)?->name)
            ->getOptionLabelsUsing(
                fn (Select $component, array $values): array => $component->getModel()::whereIn('id', $values)
                    ->pluck('name', 'id')
                    ->toArray()
            )
            ->createOptionForm(
                fn (Select $component, Form $form): array|Form|null => $form->model(
                    $component->getRelationship()
                        ? $component->getRelationship()->getModel()::class
                        : $component->getModel()
                )
                    ->schema(WidgetResource::getFormSchema($form))
            )
            ->createOptionUsing(static function ($livewire, Select $component, array $data, ComponentContainer $form) {
                $record = $component->getRelationship()?->getRelated() ?? new ($component->getModel());
                $record->fill($data);
                $record->save();

                $form->model($record)->saveRelationships();

                Notification::make('save_before_continue')
                    ->title(__('capell-admin::generic.message_save_before_continue'))
                    ->success()
                    ->send();

                return $record->getKey();
            })
            ->createOptionAction(
                fn (Forms\Components\Actions\Action $action): Forms\Components\Actions\Action => $action
                    ->modalHeading(__('capell-admin::generic.widget'))
                    ->tooltip(__('capell-admin::button.create_widget'))
                    ->modalWidth(ModalWidthEnum::Default->value)
                    ->slideOver()
                    ->closeModalByClickingAway(false)
                    ->closeModalByEscaping()
                    ->hidden(fn ($state): bool => ! $this->isMultiple() && $state)
                    ->successNotificationTitle(
                        fn (Forms\Components\Actions\Action $action): string => __(
                            'capell-admin::notification.created_successfully',
                            ['name' => $action->getModalHeading()]
                        )
                    )
                    ->after(function (Forms\Components\Actions\Action $action): void {
                        $action->success();
                    })
            );
    }

    public function withEditForm(): self
    {
        return $this->editOptionForm(fn ($state, Form $form): ?array => $state ? WidgetResource::getFormSchema($form) :
            null)
            ->editOptionAction(
                fn (Forms\Components\Actions\Action $action): Forms\Components\Actions\Action => $action
                    ->modalHeading(function (string $context, self $component, ?int $state): ?HtmlString {
                        if ($state === null || $state === 0) {
                            return null;
                        }

                        $selectedRecord = $component->getSelectedRecord();

                        return new HtmlString(__('capell-admin::heading.edit_widget_record', ['name' => $selectedRecord->name]));
                    })
                    ->modalWidth(ModalWidthEnum::Default->value)
                    ->slideOver()
                    ->visible(fn ($state): bool => (bool) $state)
                    ->successNotificationTitle(
                        fn (Forms\Components\Actions\Action $action): string => __(
                            'capell-admin::notification.updated_successfully',
                            ['name' => $action->getModalHeading()]
                        )
                    )
                    ->after(function (Forms\Components\Actions\Action $action): void {
                        $action->success();
                    })
            )
            ->fillEditOptionActionFormUsing(static function (self $component): array {
                $record = $component->getSelectedRecord();

                return $record?->attributesToArray() ?? [];
            });
    }
}
