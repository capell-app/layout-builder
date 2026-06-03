<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Capell\Admin\Filament\Concerns\HasCustomSelectOption;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Schemas\WidgetForm;
use Capell\LayoutBuilder\Models\Widget;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class WidgetSelect extends Select
{
    use HasCustomSelectOption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout-builder::form.select_widget'));
    }

    public function withCreateForm(): self
    {
        return $this->model(Widget::class)
            ->getOptionLabelFromRecordUsing(
                fn (Widget $record): string => static::getSelectOption($record),
            )
            ->getOptionLabelUsing(function (Select $component, ?int $value): ?string {
                if ($value === null) {
                    return null;
                }

                $model = $component->getModel();

                if ($model === null) {
                    return null;
                }

                $name = $model::query()->whereKey($value)->value('name');

                return is_string($name) ? $name : null;
            })
            ->getOptionLabelsUsing(
                function (Select $component, array $values): array {
                    $model = $component->getModel();

                    return $model === null
                        ? []
                        : $model::query()->whereIn('id', $values)->pluck('name', 'id')->toArray();
                },
            )
            ->createOptionForm(
                fn (Select $component, Schema $configurator): Schema => WidgetForm::configure(
                    $configurator->model(Widget::class),
                ),
            )
            ->createOptionUsing(static function (Select $component, array $data, Schema $configurator) {
                $record = new Widget;
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
                    ->modalHeading(__('capell-layout-builder::generic.widget'))
                    ->tooltip(__('capell-layout-builder::button.create_widget'))
                    ->modalWidth(Width::ScreenLarge)
                    ->slideOver()
                    ->closeModalByClickingAway(false)
                    ->closeModalByEscaping()
                    ->hidden(fn (?int $state): bool => ! $this->isMultiple() && $state !== null)
                    ->successNotificationTitle(
                        fn (Action $action): string => __(
                            'capell-admin::notification.created_successfully',
                            ['name' => $this->htmlableText($action->getModalHeading())],
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
            fn (?int $state, Schema $configurator): ?Schema => $state !== null ? WidgetForm::configure($configurator) : null,
        )
            ->editOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(function (string $context, self $component, ?int $state): ?HtmlString {
                        if ($state === null || $state === 0) {
                            return null;
                        }

                        $name = $component->getSelectedRecord()?->getAttribute('name');

                        return new HtmlString(__('capell-layout-builder::heading.edit_widget_record', ['name' => $name]));
                    })
                    ->modalWidth(Width::ScreenLarge)
                    ->slideOver()
                    ->successNotificationTitle(
                        fn (Action $action): string => __(
                            'capell-admin::notification.updated_successfully',
                            ['name' => $this->htmlableText($action->getModalHeading())],
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

    private function htmlableText(Htmlable|string|null $value): string
    {
        return $value instanceof Htmlable ? $value->toHtml() : (string) $value;
    }
}
