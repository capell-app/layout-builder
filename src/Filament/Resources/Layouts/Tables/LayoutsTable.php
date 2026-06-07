<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Layouts\Tables;

use Capell\Admin\Enums\FilamentColorEnum;
use Capell\Admin\Filament\Components\Tables\Columns\ImageColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\LayoutBuilder\Actions\BulkChanges\ApplyLayoutBulkChangeRunAction;
use Capell\LayoutBuilder\Actions\BulkChanges\PreviewLayoutBulkChangeAction;
use Capell\LayoutBuilder\Actions\BulkChanges\QueueLayoutBulkChangeRunAction;
use Capell\LayoutBuilder\Actions\GetLayoutPreviewImageUrlAction;
use Capell\LayoutBuilder\Data\LayoutBulkChangeCriteriaData;
use Capell\LayoutBuilder\Data\LayoutBulkWidgetOperationData;
use Capell\LayoutBuilder\Enums\LayoutBulkWidgetOperationType;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutBuilderPermissionRegistrar;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewMetaKey;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Layout\Component;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use LogicException;
use Override;

class LayoutsTable extends \Capell\Admin\Filament\Resources\Layouts\Tables\LayoutsTable
{
    public static function configure(Table $table): Table
    {
        return parent::configure($table)
            ->headerActions([
                self::getBulkChangeLayoutsAction(),
            ]);
    }

    #[Override]
    protected static function getTableQueryModifier(Builder $query): Builder
    {
        return parent::getTableQueryModifier($query);
    }

    #[Override]
    protected static function getTableActions(): array
    {
        return [
            self::getLayoutInfoAction(),
            ...parent::getTableActions(),
        ];
    }

    protected static function getLayoutInfoAction(): Action
    {
        return Action::make('info')
            ->label(__('capell-layout-builder::button.info'))
            ->icon('heroicon-o-information-circle')
            ->iconButton()
            ->color('info')
            ->schema(fn (Layout $record): array => [
                ViewEntry::make('widgets')
                    ->view(
                        'capell-layout-builder::components.infolists.entries.layout-widgets',
                        [
                            'widgets' => self::widgetWidgetsForLayout($record),
                        ],
                    ),
            ]);
    }

    protected static function getBulkChangeLayoutsAction(): Action
    {
        return Action::make('bulkChangeLayouts')
            ->label(__('capell-layout-builder::button.bulk_change_layouts'))
            ->icon('heroicon-o-arrows-right-left')
            ->authorize(fn (): bool => auth()->user()?->can(LayoutBuilderPermissionRegistrar::bulkMutateLayoutsPermission()) === true)
            ->slideOver()
            ->modalWidth('7xl')
            ->modalSubmitActionLabel(__('capell-layout-builder::button.approve_bulk_change'))
            ->steps([
                Step::make(__('capell-layout-builder::generic.bulk_change_criteria'))
                    ->description(__('capell-layout-builder::message.bulk_change_criteria_description'))
                    ->columns(2)
                    ->schema([
                        Hidden::make('preview_run_uuid'),
                        Select::make('site_ids')
                            ->label(__('capell-layout-builder::form.sites'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => Site::query()->ordered()->pluck('name', 'id')->all()),
                        Select::make('theme_ids')
                            ->label(__('capell-layout-builder::form.themes'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => Theme::query()->ordered()->pluck('name', 'id')->all()),
                        Select::make('groups')
                            ->label(__('capell-layout-builder::form.groups'))
                            ->multiple()
                            ->searchable()
                            ->options(fn (): array => Layout::getGroups()),
                        Select::make('layout_keys')
                            ->label(__('capell-layout-builder::form.layout_keys'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => Layout::query()->ordered()->pluck('name', 'key')->all()),
                        Toggle::make('active_only')
                            ->label(__('capell-layout-builder::form.active_layouts_only'))
                            ->default(true),
                        Select::make('require_widget_key')
                            ->label(__('capell-layout-builder::form.require_widget'))
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => self::widgetOptions()),
                        Select::make('operation_type')
                            ->label(__('capell-layout-builder::form.operation'))
                            ->required()
                            ->live()
                            ->options([
                                LayoutBulkWidgetOperationType::MoveWidget->value => __('capell-layout-builder::form.operation_move_widget'),
                                LayoutBulkWidgetOperationType::RemoveWidget->value => __('capell-layout-builder::form.operation_remove_widget'),
                                LayoutBulkWidgetOperationType::SwapWidgets->value => __('capell-layout-builder::form.operation_swap_widgets'),
                                LayoutBulkWidgetOperationType::MoveWidgetToContainer->value => __('capell-layout-builder::form.operation_move_widget_to_container'),
                            ]),
                        Select::make('source_widget_key')
                            ->label(__('capell-layout-builder::form.source_widget'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => self::widgetOptions()),
                        Select::make('target_widget_key')
                            ->label(__('capell-layout-builder::form.target_widget'))
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => self::widgetOptions())
                            ->visible(fn (Get $get): bool => in_array($get('operation_type'), [
                                LayoutBulkWidgetOperationType::MoveWidget->value,
                                LayoutBulkWidgetOperationType::SwapWidgets->value,
                                LayoutBulkWidgetOperationType::MoveWidgetToContainer->value,
                            ], true)),
                        TextInput::make('source_container_key')
                            ->label(__('capell-layout-builder::form.source_container'))
                            ->datalist(fn (): array => self::containerKeyOptions()),
                        TextInput::make('target_container_key')
                            ->label(__('capell-layout-builder::form.target_container'))
                            ->required(fn (Get $get): bool => $get('operation_type') === LayoutBulkWidgetOperationType::MoveWidgetToContainer->value)
                            ->datalist(fn (): array => self::containerKeyOptions())
                            ->visible(fn (Get $get): bool => $get('operation_type') === LayoutBulkWidgetOperationType::MoveWidgetToContainer->value),
                        Select::make('placement')
                            ->label(__('capell-layout-builder::form.placement'))
                            ->default('after')
                            ->options(fn (Get $get): array => $get('operation_type') === LayoutBulkWidgetOperationType::MoveWidgetToContainer->value
                                ? [
                                    'top' => __('capell-layout-builder::form.placement_top'),
                                    'bottom' => __('capell-layout-builder::form.placement_bottom'),
                                    'before' => __('capell-layout-builder::form.placement_before'),
                                    'after' => __('capell-layout-builder::form.placement_after'),
                                ]
                                : [
                                    'before' => __('capell-layout-builder::form.placement_before'),
                                    'after' => __('capell-layout-builder::form.placement_after'),
                                ]),
                        Select::make('occurrence_mode')
                            ->label(__('capell-layout-builder::form.occurrence_mode'))
                            ->default('all')
                            ->live()
                            ->options([
                                'all' => __('capell-layout-builder::form.occurrence_mode_all'),
                                'first' => __('capell-layout-builder::form.occurrence_mode_first'),
                                'specific' => __('capell-layout-builder::form.occurrence_mode_specific'),
                            ]),
                        TextInput::make('source_occurrence_number')
                            ->label(__('capell-layout-builder::form.source_occurrence_number'))
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn (Get $get): bool => $get('occurrence_mode') === 'specific')
                            ->required(fn (Get $get): bool => $get('occurrence_mode') === 'specific'),
                        Select::make('remove_widget_asset_mode')
                            ->label(__('capell-layout-builder::form.remove_widget_asset_mode'))
                            ->default('warn')
                            ->options([
                                'warn' => __('capell-layout-builder::form.remove_widget_asset_mode_warn'),
                                'delete_page_scoped' => __('capell-layout-builder::form.remove_widget_asset_mode_delete_page_scoped'),
                            ])
                            ->visible(fn (Get $get): bool => $get('operation_type') === LayoutBulkWidgetOperationType::RemoveWidget->value),
                    ])
                    ->afterValidation(function (Get $get, Set $set): void {
                        $run = PreviewLayoutBulkChangeAction::run(
                            criteria: self::bulkChangeCriteriaFromForm($get),
                            operation: self::bulkChangeOperationFromForm($get),
                            actorId: is_numeric(auth()->id()) ? (int) auth()->id() : null,
                        );

                        $set('preview_run_uuid', $run->uuid);
                    }),
                Step::make(__('capell-layout-builder::generic.review_bulk_change'))
                    ->description(__('capell-layout-builder::message.review_bulk_change_description'))
                    ->schema([
                        Placeholder::make('preview_summary')
                            ->label('')
                            ->content(fn (Get $get): HtmlString => self::bulkChangePreviewHtml($get)),
                    ]),
            ])
            ->action(function (array $data): void {
                $uuid = is_string($data['preview_run_uuid'] ?? null) ? $data['preview_run_uuid'] : null;
                $run = $uuid === null ? null : LayoutBulkChangeRun::query()->where('uuid', $uuid)->first();
                $queued = false;

                if (! $run instanceof LayoutBulkChangeRun) {
                    Notification::make()
                        ->title(__('capell-layout-builder::message.bulk_change_preview_missing'))
                        ->danger()
                        ->send();

                    throw new Halt;
                }

                try {
                    if (self::shouldQueueBulkChangeRun($run)) {
                        QueueLayoutBulkChangeRunAction::run(
                            run: $run,
                            actorId: is_numeric(auth()->id()) ? (int) auth()->id() : null,
                        );
                        $queued = true;
                        $summary = ['applied_layouts' => 0];
                    } else {
                        $summary = ApplyLayoutBulkChangeRunAction::run(
                            run: $run,
                            actorId: is_numeric(auth()->id()) ? (int) auth()->id() : null,
                        );
                    }
                } catch (LogicException $exception) {
                    Notification::make()
                        ->title($exception->getMessage())
                        ->danger()
                        ->send();

                    throw new Halt;
                }

                if ($queued) {
                    Notification::make()
                        ->title(__('capell-layout-builder::message.bulk_change_queued'))
                        ->success()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__('capell-layout-builder::message.bulk_change_applied'))
                    ->body(__('capell-layout-builder::message.bulk_change_applied_body', [
                        'count' => (int) ($summary['applied_layouts'] ?? 0),
                    ]))
                    ->success()
                    ->send();
            });
    }

    #[Override]
    protected static function getTableColumns(): array
    {
        $columns = parent::getTableColumns();

        $nameColumnIndex = array_search(
            NameColumn::class,
            array_map(fn (Column|Component $column): string => $column::class, $columns),
            true,
        );

        $usesCardLayout = collect($columns)->contains(fn (Column|Component $column): bool => $column instanceof View);

        if ($nameColumnIndex !== false && ! $usesCardLayout) {
            array_splice($columns, $nameColumnIndex + 1, 0, [
                TextColumn::make('layout_widgets')
                    ->label(__('capell-layout-builder::table.container_widgets'))
                    ->getStateUsing(fn (Layout $record): array => self::widgetWidgetsForLayout($record)
                        ->pluck('name')
                        ->all())
                    ->wrap()
                    ->color(FilamentColorEnum::LightGray->value)
                    ->bulleted()
                    ->limitList()
                    ->expandableLimitedList()
                    ->toggleable(),
            ]);
        }

        foreach ($columns as $column) {
            if (! $column instanceof ImageColumn) {
                continue;
            }

            if ($column->getName() !== 'admin.image') {
                continue;
            }

            $column->getStateUsing(fn (Layout $record): ?string => GetLayoutPreviewImageUrlAction::run($record));
        }

        $imageColumnIndex = array_search(
            ImageColumn::class,
            array_map(fn (Column|Component $column): string => $column::class, $columns),
            true,
        );

        if ($imageColumnIndex !== false && ! $usesCardLayout) {
            array_splice($columns, $imageColumnIndex + 1, 0, [
                TextColumn::make('admin.' . LayoutPreviewMetaKey::STATUS)
                    ->label(__('capell-layout-builder::table.generated_preview'))
                    ->badge()
                    ->toggleable(),
            ]);
        }

        return $columns;
    }

    #[Override]
    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('widget_key')
                ->label(__('capell-layout-builder::form.widget'))
                ->options(fn () => Widget::query()
                    ->pluck('name', 'key')
                    ->all())
                ->indicateUsing(function (array $state): array {
                    $indicators = [];

                    if (isset($state['value']) && $state['value'] !== '') {
                        $indicators['widget_key'] = __(
                            'capell-layout-builder::filter.widget',
                            ['search' => Widget::query()->where('key', $state['value'])->value('name')],
                        );
                    }

                    return $indicators;
                })
                ->modifyQueryUsing(
                    fn (Builder $query, array $state) => $query->when(
                        isset($state['value']) && $state['value'] !== '',
                        fn (Builder $query): Builder => self::whereContainsWidgetKey($query, (string) $state['value']),
                    ),
                ),
            ...parent::getTableFilters(),
        ];
    }

    /**
     * @return Collection<int, Widget>
     */
    private static function widgetWidgetsForLayout(Layout $layout): Collection
    {
        $widgetKeys = $layout->widgets;

        if ($widgetKeys === []) {
            return collect();
        }

        return Widget::query()
            ->whereIn('key', $widgetKeys)
            ->get()
            ->sortBy(fn (Widget $widget): int => array_search($widget->key, $widgetKeys, true) ?: 0)
            ->values();
    }

    /** @return array<string, string> */
    private static function widgetOptions(): array
    {
        return Widget::query()
            ->orderBy('name')
            ->pluck('name', 'key')
            ->all();
    }

    /** @return list<string> */
    private static function containerKeyOptions(): array
    {
        return Layout::query()
            ->get(['containers'])
            ->flatMap(function (Layout $layout): array {
                $containers = $layout->getAttribute('containers');

                return is_array($containers) ? array_keys($containers) : [];
            })
            ->map(static fn (mixed $key): string => (string) $key)
            ->filter(static fn (string $key): bool => $key !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private static function bulkChangeCriteriaFromForm(Get $get): LayoutBulkChangeCriteriaData
    {
        return LayoutBulkChangeCriteriaData::fromPayload([
            'site_ids' => self::arrayState($get('site_ids')),
            'theme_ids' => self::arrayState($get('theme_ids')),
            'groups' => self::arrayState($get('groups')),
            'layout_keys' => self::arrayState($get('layout_keys')),
            'active_only' => (bool) $get('active_only'),
            'require_widget_key' => $get('require_widget_key'),
        ]);
    }

    private static function bulkChangeOperationFromForm(Get $get): LayoutBulkWidgetOperationData
    {
        return LayoutBulkWidgetOperationData::fromPayload([
            'type' => $get('operation_type'),
            'source_widget_key' => $get('source_widget_key'),
            'target_widget_key' => $get('target_widget_key'),
            'source_container_key' => $get('source_container_key'),
            'target_container_key' => $get('target_container_key'),
            'placement' => $get('placement'),
            'occurrence_mode' => $get('occurrence_mode'),
            'source_occurrence_number' => $get('source_occurrence_number'),
            'remove_widget_asset_mode' => $get('remove_widget_asset_mode'),
        ]);
    }

    private static function shouldQueueBulkChangeRun(LayoutBulkChangeRun $run): bool
    {
        $summary = $run->summary ?? [];

        return (int) ($summary['target_layouts'] ?? 0) >= 50
            || (int) ($summary['target_pages'] ?? 0) >= 250;
    }

    private static function bulkChangePreviewHtml(Get $get): HtmlString
    {
        $uuid = $get('preview_run_uuid');
        $run = is_string($uuid)
            ? LayoutBulkChangeRun::query()->with(['results.layout'])->where('uuid', $uuid)->first()
            : null;

        return new HtmlString(view('capell-layout-builder::filament.actions.layout-bulk-change-review', [
            'run' => $run,
        ])->render());
    }

    /**
     * @return list<mixed>
     */
    private static function arrayState(mixed $state): array
    {
        return is_array($state) ? array_values($state) : [];
    }

    /**
     * @param  Builder<Layout>  $query
     * @return Builder<Layout>
     */
    private static function whereContainsWidgetKey(Builder $query, string $widgetKey): Builder
    {
        $escapedWidgetKey = addcslashes($widgetKey, '\%_');

        return $query->where(function (Builder $query) use ($escapedWidgetKey): void {
            $query
                ->where('containers', 'like', '%"widget_key":"' . $escapedWidgetKey . '"%')
                ->orWhere('containers', 'like', '%"widgets":["' . $escapedWidgetKey . '"%')
                ->orWhere('containers', 'like', '%,"' . $escapedWidgetKey . '"%');
        });
    }
}
