<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Capell\Admin\Data\RecordRelationshipCountData;
use Capell\Admin\Data\RecordStateData;
use Capell\Admin\Filament\Concerns\HasCustomSelectOption;
use Capell\LayoutBuilder\Actions\BuildWidgetDeletionImpactAction;
use Capell\LayoutBuilder\Data\WidgetLayoutUsageData;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Schemas\WidgetForm;
use Capell\LayoutBuilder\Models\Widget;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
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
            ->options(fn (): array => Widget::query()
                ->where('status', true)
                ->orderBy('order')
                ->orderBy('name')
                ->tap(fn (Builder $query): Builder => $this->applyWidgetLayoutsCount($query))
                ->get()
                ->mapWithKeys(function (Widget $record): array {
                    $key = $record->getKey();

                    if (! is_int($key) && ! is_string($key)) {
                        return [];
                    }

                    return [$key => $this->optionLabel($record)];
                })
                ->all())
            ->getOptionLabelFromRecordUsing(
                fn (Widget $record): string => $this->optionLabel($record),
            )
            ->getOptionLabelUsing(function (Select $component, ?int $value): ?string {
                if ($value === null) {
                    return null;
                }

                return $this->optionLabelForId($value);
            })
            ->getOptionLabelsUsing(
                fn (Select $component, array $values): array => Widget::query()
                    ->withTrashed()
                    ->tap(fn (Builder $query): Builder => $this->applyWidgetLayoutsCount($query))
                    ->whereKey($values)
                    ->get()
                    ->mapWithKeys(function (Widget $record): array {
                        $key = $record->getKey();

                        if (! is_int($key) && ! is_string($key)) {
                            return [];
                        }

                        return [$key => $this->optionLabel($record)];
                    })
                    ->all(),
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

    private function optionLabelForId(int $id): ?string
    {
        /** @var Widget|null $record */
        $record = Widget::query()
            ->withTrashed()
            ->tap(fn (Builder $query): Builder => $this->applyWidgetLayoutsCount($query))
            ->find($id);

        return $record instanceof Widget ? $this->optionLabel($record) : null;
    }

    private function optionLabel(Widget $record): string
    {
        $impact = $this->deletionImpact($record);

        return static::getSelectOption($record, [
            'label' => $record->name,
            'states' => array_values(array_filter([
                $record->trashed() ? new RecordStateData(
                    key: 'unavailable',
                    label: (string) __('capell-admin::widget.unavailable'),
                    description: (string) __('capell-admin::widget.unavailable_help'),
                    color: 'danger',
                    icon: Heroicon::OutlinedExclamationTriangle,
                    priority: 10,
                ) : null,
                $record->isDisabled() ? new RecordStateData(
                    key: 'disabled',
                    label: (string) __('capell-admin::generic.disabled'),
                    description: (string) __('capell-layout-builder::table.widget_usage_disabled_tooltip'),
                    color: 'danger',
                    icon: Heroicon::OutlinedEyeSlash,
                    priority: 20,
                ) : null,
                $impact->layouts === 0 && $impact->isAuthoritative ? new RecordStateData(
                    key: 'unused',
                    label: (string) __('capell-layout-builder::table.widget_usage_unused'),
                    description: (string) __('capell-layout-builder::table.widget_usage_unused_tooltip'),
                    color: 'warning',
                    icon: Heroicon::OutlinedExclamationTriangle,
                    priority: 30,
                ) : ($impact->layouts === 0 ? new RecordStateData(
                    key: 'no-tracked-uses',
                    label: (string) __('capell-layout-builder::table.widget_usage_no_tracked_uses'),
                    description: (string) __('capell-layout-builder::table.widget_usage_no_tracked_uses_tooltip'),
                    color: 'warning',
                    icon: Heroicon::OutlinedExclamationTriangle,
                    priority: 30,
                ) : new RecordStateData(
                    key: 'used',
                    label: trans_choice('capell-layout-builder::table.widget_usage_layouts', $impact->layouts, ['count' => $impact->layouts]),
                    description: trans_choice('capell-layout-builder::table.widget_usage_layouts_tooltip', $impact->layouts, ['count' => $impact->layouts]),
                    color: 'success',
                    icon: Heroicon::OutlinedLink,
                    priority: 30,
                    isExceptional: false,
                )),
            ])),
            'relationships' => [
                new RecordRelationshipCountData(
                    key: 'layouts',
                    label: (string) __('capell-admin::table.total_layouts'),
                    count: $impact->layouts,
                ),
            ],
        ]);
    }

    private function deletionImpact(Widget $record): WidgetLayoutUsageData
    {
        return BuildWidgetDeletionImpactAction::run($record);
    }

    /**
     * Apply Widget's protected local scope through Eloquent's public scope API.
     *
     * @param  Builder<Widget>  $query
     * @return Builder<Widget>
     */
    private function applyWidgetLayoutsCount(Builder $query): Builder
    {
        $query->getModel()->callNamedScope('withLayoutsCount', [
            $query,
            LayoutResource::getEloquentQuery(),
        ]);

        return $query;
    }
}
