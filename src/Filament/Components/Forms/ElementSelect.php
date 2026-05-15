<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Capell\Admin\Filament\Concerns\HasCustomSelectOption;
use Capell\LayoutBuilder\Filament\Resources\Elements\Schemas\ElementForm;
use Capell\LayoutBuilder\Models\Element;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;

class ElementSelect extends Select
{
    use HasCustomSelectOption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout-builder::form.select_element'));
    }

    public function withCreateForm(): self
    {
        return $this->model(Element::class)
            ->getOptionLabelFromRecordUsing(
                fn (Element $record): string => static::getSelectOption($record),
            )
            ->getOptionLabelUsing(function (Select $component, ?int $value): ?string {
                if ($value === null) {
                    return null;
                }

                $name = $component->getModel()::query()->whereKey($value)->value('name');

                return is_string($name) ? $name : null;
            })
            ->getOptionLabelsUsing(
                fn (Select $component, array $values): array => $component->getModel()::whereIn('id', $values)
                    ->pluck('name', 'id')
                    ->toArray(),
            )
            ->createOptionForm(
                fn (Select $component, Schema $configurator): Schema => ElementForm::configure(
                    $configurator->model(Element::class),
                ),
            )
            ->createOptionUsing(static function (Select $component, array $data, Schema $configurator) {
                $record = new Element;
                $record->fill($data);
                $record->save();

                $configurator->model($record)->saveRelationships();

                Notification::make('save_before_continue')
                    ->title(__('capell-admin::generic.message_save_before_continue'))
                    ->success()
                    ->send();

                return $record->getKey();
            })
            ->createOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(__('capell-layout-builder::generic.element'))
                    ->tooltip(__('capell-layout-builder::button.create_element'))
                    ->modalWidth(Width::ScreenLarge)
                    ->slideOver()
                    ->closeModalByClickingAway(false)
                    ->closeModalByEscaping()
                    ->hidden(fn (?int $state): bool => ! $this->isMultiple() && $state !== null)
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
            fn (?int $state, Schema $configurator): ?Schema => $state !== null ? ElementForm::configure($configurator) : null,
        )
            ->editOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(function (string $context, self $component, ?int $state): ?HtmlString {
                        if ($state === null || $state === 0) {
                            return null;
                        }

                        $name = $component->getSelectedRecord()?->getAttribute('name');

                        return new HtmlString(__('capell-layout-builder::heading.edit_element_record', ['name' => $name]));
                    })
                    ->modalWidth(Width::ScreenLarge)
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
