<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Concerns\HasCustomSelectOption;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Filament\Resources\Widgets\Schemas\WidgetForm;
use Capell\Layout\Models\Widget;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;

class WidgetSelect extends Select
{
    use HasCustomSelectOption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout::form.select_widget'));
    }

    public function withCreateForm(): self
    {
        return $this->model(CapellCore::getModel(ModelEnum::Widget->name))
            ->getOptionLabelFromRecordUsing(
                fn (Widget $record): string => static::getSelectOption($record),
            )
            ->getOptionLabelUsing(fn (Select $component, $value): ?string => $component->getModel()::find($value)?->name)
            ->getOptionLabelsUsing(
                fn (Select $component, array $values): array => $component->getModel()::whereIn('id', $values)
                    ->pluck('name', 'id')
                    ->toArray(),
            )
            ->createOptionForm(
                fn (Select $component, Schema $schema): Schema => WidgetForm::configure(
                    $schema->model(
                        $component->getRelationship()
                            ? $component->getRelationship()->getModel()::class
                            : $component->getModel(),
                    ),
                ),
            )
            ->createOptionUsing(static function ($livewire, Select $component, array $data, Schema $schema) {
                $record = $component->getRelationship()?->getRelated() ?? new ($component->getModel());
                $record->fill($data);
                $record->save();

                $schema->model($record)->saveRelationships();

                Notification::make('save_before_continue')
                    ->title(__('capell-admin::generic.message_save_before_continue'))
                    ->success()
                    ->send();

                return $record->getKey();
            })
            ->createOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(__('capell-admin::generic.widget'))
                    ->tooltip(__('capell-layout::button.create_widget'))
                    ->modalWidth(Width::SixExtraLarge)
                    ->slideOver()
                    ->closeModalByClickingAway(false)
                    ->closeModalByEscaping()
                    ->hidden(fn ($state): bool => ! $this->isMultiple() && $state)
                    ->successNotificationTitle(
                        fn (Action $action): string => __(
                            'capell-admin::notification.created_successfully',
                            ['name' => $action->getModalHeading()],
                        ),
                    )
                    ->after(function (Action $action): void {
                        $action->success();
                    }),
            );
    }

    public function withEditForm(): self
    {
        return $this->editOptionForm(
            fn ($state, Schema $schema): ?Schema => $state ? WidgetForm::configure($schema) : null,
        )
            ->editOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(function (string $context, self $component, ?int $state): ?HtmlString {
                        if ($state === null || $state === 0) {
                            return null;
                        }

                        $selectedRecord = $component->getSelectedRecord();

                        return new HtmlString(__('capell-layout::heading.edit_widget_record', ['name' => $selectedRecord->name]));
                    })
                    ->modalWidth(Width::SixExtraLarge)
                    ->slideOver()
                    ->successNotificationTitle(
                        fn (Action $action): string => __(
                            'capell-admin::notification.updated_successfully',
                            ['name' => $action->getModalHeading()],
                        ),
                    )
                    ->after(function (Action $action): void {
                        $action->success();
                    }),
            )
            ->fillEditOptionActionFormUsing(static function (self $component): array {
                $record = $component->getSelectedRecord();

                return $record?->attributesToArray() ?? [];
            });
    }
}
