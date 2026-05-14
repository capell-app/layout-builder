<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Concerns;

use BackedEnum;
use Capell\Admin\Actions\ReplicateLayoutAction;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Core\Facades\CapellCore;
use Capell\Core\LayoutBuilder\Exceptions\MissingWidgetAssetException;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Widget;
use Capell\Core\Models\WidgetAsset;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Resources\Pages\Tables\PageSelectionTable;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Schemas\WidgetAssetForm;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Schemas\WidgetForm;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Tables\WidgetSelectionTable;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TableSelect;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\HtmlString;

trait HasLayoutActions
{
    public function saveLayoutAction(): Action
    {
        return Action::make('saveLayout')
            ->label(__('capell-layout-builder::button.save_layout'))
            ->color('primary')
            ->size(Size::Small)
            ->button()
            ->outlined()
            ->action(function (Action $action, self $livewire): void {
                if (! $livewire->saveLayout(withNotifications: true)) {
                    $action->failure();

                    return;
                }

                $action->success();
            });
    }

    public function duplicateLayoutAction(): Action
    {
        return Action::make('duplicateLayout')
            ->label(__('capell-layout-builder::button.copy_layout'))
            ->groupedIcon('heroicon-o-square-2-stack')
            ->modalWidth(Width::ScreenSmall)
            ->requiresConfirmation()
            ->modalDescription(__('capell-layout-builder::message.copy_layout_confirmation'))
            ->visible(fn (): bool => $this->inPageContext())
            ->action(function (Action $action, self $livewire): void {
                $livewire->duplicateLayout();

                $livewire->layoutUpdated();

                $action->success();
            });
    }

    public function cloneLayoutForPageAction(): Action
    {
        return Action::make('cloneLayoutForPage')
            ->label(__('capell-layout-builder::button.clone'))
            ->tooltip(__('capell-layout-builder::button.clone_layout_for_page'))
            ->icon('heroicon-o-square-2-stack')
            ->color('primary')
            ->size(Size::Small)
            ->button()
            ->modalWidth(Width::ScreenSmall)
            ->requiresConfirmation()
            ->modalDescription(__('capell-layout-builder::message.clone_layout_for_page_confirmation'))
            ->visible(fn (): bool => $this->layoutIsSharedWithOtherPages)
            ->action(function (Action $action, self $livewire): void {
                $livewire->duplicateLayout();

                $livewire->layoutUpdated();

                Notification::make('layout-cloned-for-page')
                    ->body(__('capell-layout-builder::message.layout_cloned_for_page'))
                    ->success()
                    ->send();

                $action->success();
            });
    }

    public function undoLayoutMutationAction(): Action
    {
        return Action::make('undoLayoutMutation')
            ->label(__('capell-layout-builder::button.undo'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->size(Size::Small)
            ->link()
            ->visible(fn (): bool => $this->layoutUndoSnapshots !== [])
            ->action(fn (): null => $this->undoLayoutMutation());
    }

    public function redoLayoutMutationAction(): Action
    {
        return Action::make('redoLayoutMutation')
            ->label(__('capell-layout-builder::button.redo'))
            ->icon('heroicon-o-arrow-uturn-right')
            ->color('gray')
            ->size(Size::Small)
            ->link()
            ->visible(fn (): bool => $this->layoutRedoSnapshots !== [])
            ->action(fn (): null => $this->redoLayoutMutation());
    }

    public function addContainerAction(): Action
    {
        return Action::make('addContainer')
            ->label(__('capell-layout-builder::button.container'))
            ->tooltip(__('capell-layout-builder::button.add_container'))
            ->icon('heroicon-m-plus')
            ->color('gray')
            ->outlined()
            ->size(Size::Small)
            ->extraAttributes(['class' => 'layout-builder-add-container-button'])
            ->record(fn (): Layout => $this->layout)
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitActionLabel(fn (Action $action): string => $action->getTooltip())
            ->schema(
                static fn (self $livewire, Schema $schema, array $arguments): Schema => $schema->operation('createOption')
                    ->schema($livewire->getContainerSchema($schema, $arguments)),
            )
            ->action(function (Action $action, self $livewire, array $data, array $arguments): void {
                $livewire->saveContainer(
                    $data,
                    position: isset($arguments['position']) ? (int) $arguments['position'] : null,
                );

                $action->success();
            });
    }

    public function editContainerAction(): Action
    {
        return Action::make('editContainer')
            ->label(__('capell-layout-builder::button.edit_container'))
            ->groupedIcon('heroicon-o-pencil')
            ->size(Size::Small)
            ->color('gray')
            ->grouped()
            ->record(fn (): Layout => $this->layout)
            ->modalWidth(Width::ScreenLarge)
            ->modalHeading(
                fn (array $arguments): string|array|null => __(
                    'capell-layout-builder::heading.edit_container',
                    ['key' => str($arguments['containerKey'])->title()],
                ),
            )
            ->modalSubmitActionLabel(fn (Action $action): string => $action->getLabel())
            ->schema(
                static fn (self $livewire, Schema $schema, array $arguments): Schema => $schema->operation('editOption')
                    ->schema($livewire->getContainerSchema($schema, $arguments)),
            )
            ->fillForm(fn (self $livewire, array $arguments): array => [
                'key' => $arguments['containerKey'],
                'meta' => $livewire->containers[$arguments['containerKey']]['meta'] ?? [],
            ])
            ->action(function (Action $action, self $livewire, array $data, array $arguments): void {
                $livewire->saveContainer($data, $arguments['containerKey']);

                $action->success();
            });
    }

    public function removeContainerAction(): Action
    {
        return Action::make('removeContainer')
            ->label(__('capell-layout-builder::button.remove_container'))
            ->groupedIcon('heroicon-m-trash')
            ->color('danger')
            ->size(Size::Small)
            ->grouped()
            ->requiresConfirmation()
            ->modalDescription(__('capell-layout-builder::message.remove_container_confirmation'))
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->removeContainer($arguments['containerKey']);

                $action->success();
            });
    }

    public function moveContainerUpAction(): Action
    {
        return Action::make('moveContainerUp')
            ->label(__('capell-layout-builder::button.move_up'))
            ->groupedIcon('heroicon-o-arrow-up')
            ->color('gray')
            ->size(Size::Small)
            ->grouped()
            ->visible(fn (array $arguments, self $livewire): bool => $livewire->canMoveContainerUp($arguments['containerKey']))
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->moveContainerUp($arguments['containerKey']);

                $action->success();
            });
    }

    public function moveContainerDownAction(): Action
    {
        return Action::make('moveContainerDown')
            ->label(__('capell-layout-builder::button.move_down'))
            ->groupedIcon('heroicon-o-arrow-down')
            ->color('gray')
            ->size(Size::Small)
            ->grouped()
            ->visible(fn (array $arguments, self $livewire): bool => $livewire->canMoveContainerDown($arguments['containerKey']))
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->moveContainerDown($arguments['containerKey']);

                $action->success();
            });
    }

    public function duplicateContainerAction(): Action
    {
        return Action::make('duplicateContainer')
            ->label(__('capell-layout-builder::button.duplicate_container'))
            ->groupedIcon('heroicon-o-square-2-stack')
            ->color('gray')
            ->size(Size::Small)
            ->grouped()
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->duplicateContainer($arguments['containerKey']);

                $action->success();
            });
    }

    public function editLayoutWidgetAction(): Action
    {
        return Action::make('editLayoutWidget')
            ->label(__('capell-layout-builder::button.edit_layout_widget'))
            ->groupedIcon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->grouped()
            ->visible(
                fn (array $arguments, self $livewire): bool => (bool) $livewire->getContainerWidgetConfigurator(
                    $arguments['containerKey'],
                    $arguments['widgetIndex'],
                ),
            )
            ->modalHeading(__('capell-layout-builder::heading.container_widget_settings'))
            ->modalSubmitActionLabel(fn (Action $action): string => $action->getLabel())
            ->modalDescription(
                fn (array $arguments, self $livewire): string => __(
                    'capell-admin::generic.edit_container_widget',
                    [
                        'container' => $arguments['containerKey'],
                        'widget' => $livewire->getContainerWidget($arguments['containerKey'], $arguments['widgetIndex'])?->name,
                    ],
                ),
            )
            ->modalWidth(Width::ScreenSmall)
            ->schema(function (array $arguments, self $livewire, Schema $schema): Schema {
                $adminSchema = AdminSurfaceLookup::configurator(
                    ConfiguratorTypeEnum::LayoutWidget->value,
                    $livewire->getContainerWidgetConfigurator($arguments['containerKey'], $arguments['widgetIndex']),
                );

                $typeSchema = resolve($adminSchema)->make($schema);

                return $schema->operation('editOption')->components($typeSchema);
            })
            ->fillForm(
                fn (self $livewire, array $arguments): array => $livewire->containers[$arguments['containerKey']]['widgets'][$arguments['widgetIndex']]['meta'] ?? [],
            )
            ->action(function (Action $action, self $livewire, array $arguments, array $data): void {
                $livewire->editLayoutWidget($arguments['containerKey'], $arguments['widgetIndex'], $data);

                $action->success();
            });
    }

    public function addWidgetAction(): Action
    {
        return Action::make('addWidget')
            ->label(fn (array $arguments): string => isset($arguments['position'])
                ? __('capell-layout-builder::button.add_widget_here')
                : __('capell-layout-builder::button.add_widget'))
            ->tooltip(__('capell-layout-builder::button.add_widget'))
            ->modalHeading(__('capell-layout-builder::heading.add_widget_to_container'))
            ->icon('heroicon-c-plus')
            ->size(Size::Small)
            ->color('primary')
            ->button()
            ->visible(fn (): bool => (bool) $this->containers)
            ->modalWidth(Width::ScreenLarge)
            ->extraModalWindowAttributes([
                'class' => 'capell-layout-builder-builder-assets-table',
            ])
            ->closeModalByClickingAway(false)
            ->schema(function (Schema $schema, array $arguments, self $livewire): Schema {
                $containerOptions = $livewire->getContainerOptions();
                $containerKey = $arguments['containerKey'] ?? null;

                $components = [];

                if (! $containerKey && $containerOptions->count() > 1) {
                    $components[] = Select::make('container')
                        ->label(__('capell-admin::form.container'))
                        ->hiddenLabel()
                        ->prefix(fn (Select $component): string => $component->getLabel() . ': ')
                        ->required()
                        ->options($containerOptions);
                }

                $components[] = TableSelect::make('widgets')
                    ->label(__('capell-layout-builder::button.widget'))
                    ->tableConfiguration(WidgetSelectionTable::class)
                    ->multiple()
                    ->required();

                return $schema->schema($components);
            })
            ->action(function (array $data, array $arguments, self $livewire): void {
                $containerOptions = $livewire->getContainerOptions();
                $containerKey = $arguments['containerKey'] ?? null;

                if (! $containerKey) {
                    $containerKey = $containerOptions->count() === 1
                        ? $containerOptions->keys()->first()
                        : ($data['container'] ?? null);
                }

                $livewire->addWidgetsToContainer(
                    containerKey: (string) $containerKey,
                    widgets: $data['widgets'] ?? [],
                    position: isset($arguments['position']) ? (int) $arguments['position'] : null,
                );
            });
    }

    public function editWidgetAction(): Action
    {
        return Action::make('editWidget')
            ->label(__('capell-layout-builder::button.edit_widget'))
            ->tooltip(__('capell-layout-builder::button.edit_widget'))
            ->button()
            ->slideOver()
            ->closeModalByClickingAway(false)
            ->icon('heroicon-o-pencil')
            ->color('gray')
            ->size(Size::Small)
            ->modalWidth(Width::ScreenLarge)
            ->record(
                fn (array $arguments): Widget => $this->getContainerWidget(
                    $arguments['containerKey'],
                    $arguments['widgetIndex'],
                ),
            )
            ->modalHeading(fn (Widget $record): string => $record->name)
            ->modalDescription(
                fn (Widget $record): string => __(
                    'capell-layout-builder::heading.widget_type',
                    ['type' => $record->type?->name],
                ),
            )
            ->modalSubmitActionLabel(__('capell-layout-builder::button.save_changes'))
            ->successNotificationTitle(__('capell-layout-builder::message.widget_updated'))
            ->fillForm(fn (Widget $record): array => $record->attributesToArray())
            ->schema(
                fn (Action $action, Schema $schema): Schema => WidgetForm::configure(
                    $schema->operation('editOption')
                        ->record(fn (): Widget => $action->getRecord()->fresh()),
                ),
            )
            ->action(function (Action $action, Widget $record, Schema $schema, array $data): void {
                $this->saveWidgetForm(configurator: $schema, record: $record, data: $data);

                $action->success();
            });
    }

    public function duplicateWidgetAction(): Action
    {
        return Action::make('duplicateWidget')
            ->label(__('capell-layout-builder::button.duplicate_widget'))
            ->grouped()
            ->groupedIcon('heroicon-o-square-2-stack')
            ->color('gray')
            ->size('sm')
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->duplicateWidget(containerKey: $arguments['containerKey'], originalIndex: $arguments['widgetIndex']);

                $action->success();
            });
    }

    public function moveWidgetUpAction(): Action
    {
        return Action::make('moveWidgetUp')
            ->label(__('capell-layout-builder::button.move_up'))
            ->grouped()
            ->groupedIcon('heroicon-o-arrow-up')
            ->color('gray')
            ->size(Size::Small)
            ->visible(fn (array $arguments, self $livewire): bool => $livewire->canMoveWidgetUp(
                $arguments['containerKey'],
                $arguments['widgetIndex'],
            ))
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->moveWidgetUp($arguments['containerKey'], $arguments['widgetIndex']);

                $action->success();
            });
    }

    public function moveWidgetDownAction(): Action
    {
        return Action::make('moveWidgetDown')
            ->label(__('capell-layout-builder::button.move_down'))
            ->grouped()
            ->groupedIcon('heroicon-o-arrow-down')
            ->color('gray')
            ->size(Size::Small)
            ->visible(fn (array $arguments, self $livewire): bool => $livewire->canMoveWidgetDown(
                $arguments['containerKey'],
                $arguments['widgetIndex'],
            ))
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->moveWidgetDown($arguments['containerKey'], $arguments['widgetIndex']);

                $action->success();
            });
    }

    public function moveWidgetToContainerAction(): Action
    {
        return Action::make('moveWidgetToContainer')
            ->label(__('capell-layout-builder::button.move_to_container'))
            ->grouped()
            ->groupedIcon('heroicon-o-arrow-right')
            ->color('gray')
            ->size(Size::Small)
            ->modalWidth(Width::ScreenSmall)
            ->modalHeading(__('capell-layout-builder::button.move_to_container'))
            ->visible(fn (array $arguments, self $livewire): bool => $livewire->canMoveWidgetToAnotherContainer(
                $arguments['containerKey'],
                $arguments['widgetIndex'],
            ))
            ->schema(fn (self $livewire, array $arguments, Schema $schema): Schema => $schema->schema([
                Select::make('target_container')
                    ->label(__('capell-admin::form.container'))
                    ->options(
                        $livewire->getContainerOptions()
                            ->reject(fn (string $label, string $containerKey): bool => $containerKey === $arguments['containerKey'])
                            ->all(),
                    )
                    ->required(),
            ]))
            ->action(function (Action $action, self $livewire, array $arguments, array $data): void {
                $livewire->moveWidgetToContainer(
                    $arguments['containerKey'],
                    $arguments['widgetIndex'],
                    (string) $data['target_container'],
                );

                $action->success();
            });
    }

    public function removeWidgetAction(): Action
    {
        return Action::make('removeWidget')
            ->label(__('capell-layout-builder::button.remove_widget'))
            ->grouped()
            ->groupedIcon('heroicon-m-trash')
            ->color('danger')
            ->size('sm')
            ->requiresConfirmation()
            ->modalDescription(__('capell-layout-builder::message.remove_widget_confirmation'))
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->removeWidget(containerKey: $arguments['containerKey'], widgetIndex: $arguments['widgetIndex']);

                $action->success();
            });
    }

    public function selectAssetAction(): Action
    {
        return Action::make('selectAsset')
            ->label(
                fn (array $arguments): string => __(
                    'capell-layout-builder::button.select_asset',
                    ['asset' => CapellCore::getAsset($arguments['type'])->getLabel()],
                ),
            )
            ->grouped()
            ->modal()
            ->icon('heroicon-c-magnifying-glass')
            ->iconSize(IconSize::Small)
            ->size(Size::Small)
            ->extraModalWindowAttributes([
                'class' => 'capell-layout-builder-builder-assets-table',
            ])
            ->modalWidth(Width::ScreenLarge)
            ->modalHeading(function (self $livewire, array $arguments): string {
                $totalAssets = $livewire->countWidgetAssets($arguments['containerKey'], $arguments['widgetIndex']);

                if ($totalAssets !== 0) {
                    $hasPageAssets = $livewire->hasPageAssets($arguments['containerKey'], $arguments['widgetIndex']);
                } else {
                    $hasPageAssets = $livewire->inPageContext();
                }

                return $hasPageAssets
                    ? __('capell-admin::generic.select_page_widget_asset_description', ['type' => $arguments['type']])
                    : __('capell-admin::generic.select_widget_asset_description', ['type' => $arguments['type']]);
            })
            ->closeModalByClickingAway(false)
            ->schema(function (Schema $schema, array $arguments, self $livewire): Schema {
                $tableConfiguration = PageSelectionTable::class;

                $excludeIds = $livewire->getWidgetAssetsByType(
                    $arguments['containerKey'],
                    (int) $arguments['widgetIndex'],
                    $arguments['type'],
                );

                return $schema->schema([
                    TableSelect::make('assets')
                        ->tableConfiguration($tableConfiguration)
                        ->multiple()
                        ->hiddenLabel()
                        ->tableArguments([
                            'excludeIds' => $excludeIds,
                            'pageId' => $livewire->page?->getKey(),
                            'siteId' => $livewire->getSite()?->getKey(),
                        ]),
                ]);
            })
            ->action(function (array $data, array $arguments, self $livewire): void {
                $containerKey = $arguments['containerKey'];
                $widgetIndex = (int) $arguments['widgetIndex'];
                $type = $arguments['type'];

                $hasPageAssets = $livewire->countWidgetAssets($containerKey, $widgetIndex) > 0
                    ? $livewire->hasPageAssets($containerKey, $widgetIndex)
                    : $livewire->inPageContext();

                $livewire->addAssetsToWidget(
                    arguments: [
                        'containerKey' => $containerKey,
                        'widgetIndex' => $widgetIndex,
                        'hasPageAssets' => $hasPageAssets,
                    ],
                    type: $type,
                    assets: $data['assets'] ?? [],
                );
            });
    }

    public function addAssetAction(): Action
    {
        return Action::make('addAsset')
            ->label(
                fn (array $arguments): string => __(
                    'capell-layout-builder::button.add_new_asset',
                    ['asset' => CapellCore::getAsset($arguments['type'])->getLabel()],
                ),
            )
            ->icon('heroicon-o-plus-circle')
            ->iconSize(IconSize::Small)
            ->size(Size::ExtraSmall)
            ->modal()
            ->grouped()
            ->outlined()
            ->slideOver()
            ->modalWidth(Width::ScreenLarge)
            ->closeModalByClickingAway(false)
            ->modalHeading(
                fn (array $arguments, self $livewire): string => __(
                    'capell-admin::generic.add_widget_asset',
                    [
                        'widget' => $livewire->getContainerWidget($arguments['containerKey'], $arguments['widgetIndex'])?->name,
                        'asset' => $arguments['type'],
                    ],
                ),
            )
            ->modalSubmitActionLabel(
                fn (array $arguments, Action $action): string => __(
                    'capell-layout-builder::button.create_widget_asset',
                    ['type' => $arguments['type']],
                ),
            )
            ->successNotificationTitle(__('capell-layout-builder::message.asset_added'))
            ->schema(
                fn (array $arguments, Schema $schema): Schema => self::getWidgetAssetSchema(
                    $schema->operation('createOption')
                        ->record(fn (): WidgetAsset => $this->makeWidgetAssetRecordForCreate($arguments)),
                ),
            )
            ->model(fn (): string => WidgetAsset::class)
            ->fillForm(function (array $arguments): array {
                $containerKey = $arguments['containerKey'];
                $widgetIndex = $arguments['widgetIndex'];
                $assetType = $arguments['type'];

                $widget = $this->getContainerWidget($containerKey, $widgetIndex);

                $asset = CapellAdmin::getAsset($assetType);

                return [
                    'widget_id' => $widget->id,
                    'workspace_id' => $this->getCurrentWidgetAssetWorkspaceId($widget),
                    'asset_type' => $assetType,
                    'meta' => [],
                    'asset' => in_array($asset->defaultDataAction, [null, '', '0'], true)
                        ? []
                        : $asset->defaultDataAction::run(),
                ];
            })
            ->action(self::addAssetFromAction(...));
    }

    public function editWidgetAssetAction(): Action
    {
        return Action::make('editWidgetAsset')
            ->label(__('capell-admin::button.edit'))
            ->button()
            ->modal()
            ->slideOver()
            ->closeModalByClickingAway(false)
            ->color('primary')
            ->size(Size::ExtraSmall)
            ->visible(fn (self $livewire): bool => $livewire->canEditContent())
            ->icon(fn (array $arguments): string|BackedEnum => CapellCore::getAsset($arguments['type'])->getIcon())
            ->iconSize(IconSize::Small)
            ->tooltip(
                fn (array $arguments): string => __(
                    'capell-layout-builder::button.edit_asset_type',
                    ['type' => $arguments['type']],
                ),
            )
            ->modalWidth(Width::ScreenLarge)
            ->modalHeading(
                fn (self $livewire, array $arguments): string => $this->getEditWidgetAssetModalHeading($livewire, $arguments),
            )
            ->modalDescription(
                fn (self $livewire, array $arguments): ?string => $this->getEditWidgetAssetModalDescription($livewire, $arguments),
            )
            ->modalSubmitActionLabel(__('capell-layout-builder::button.save_changes'))
            ->successNotificationTitle(__('capell-layout-builder::message.asset_updated'))
            ->schema(
                fn (self $livewire, Schema $schema, array $arguments): Schema => self::getWidgetAssetSchema(
                    $schema->operation('editOption'),
                ),
            )
            ->fillForm(fn (WidgetAsset $record, array $arguments): array => [
                'meta' => $record->meta,
            ])
            ->record(fn (array $arguments): WidgetAsset => $this->resolveEditableWidgetAsset($arguments))
            ->disabled(fn (WidgetAsset $record): bool => ! $record->exists)
            ->action(
                fn (WidgetAsset $record, array $data, self $livewire, array $arguments, Action $action, Schema $schema) => $this->applyWidgetAssetUpdate(
                    record: $record,
                    data: $data,
                    livewire: $livewire,
                    arguments: $arguments,
                    action: $action,
                    configurator: $schema,
                ),
            );
    }

    public function moveAssetUpAction(): Action
    {
        return Action::make('moveAssetUp')
            ->label(__('capell-layout-builder::button.move_up'))
            ->grouped()
            ->groupedIcon('heroicon-o-arrow-up')
            ->color('gray')
            ->size(Size::Small)
            ->visible(fn (array $arguments, self $livewire): bool => $livewire->canMoveAssetUp(
                $arguments['containerKey'],
                (int) $arguments['widgetIndex'],
                (int) $arguments['assetIndex'],
            ))
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->moveAssetUp($arguments['containerKey'], (int) $arguments['widgetIndex'], (int) $arguments['assetIndex']);

                $action->success();
            });
    }

    public function moveAssetDownAction(): Action
    {
        return Action::make('moveAssetDown')
            ->label(__('capell-layout-builder::button.move_down'))
            ->grouped()
            ->groupedIcon('heroicon-o-arrow-down')
            ->color('gray')
            ->size(Size::Small)
            ->visible(fn (array $arguments, self $livewire): bool => $livewire->canMoveAssetDown(
                $arguments['containerKey'],
                (int) $arguments['widgetIndex'],
                (int) $arguments['assetIndex'],
            ))
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->moveAssetDown($arguments['containerKey'], (int) $arguments['widgetIndex'], (int) $arguments['assetIndex']);

                $action->success();
            });
    }

    public function removeAssetsAction(): Action
    {
        return Action::make('removeAssets')
            ->label(__('capell-admin::button.remove'))
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->size(Size::ExtraSmall)
            ->extraAttributes(fn (array $arguments): array => [
                'class' => 'whitespace-nowrap',
                'x-cloak' => '',
                'x-show' => new HtmlString(
                    sprintf("selectedRecords['%s'][%s].length", $arguments['containerKey'], $arguments['widgetIndex']),
                ),
            ])
            ->successNotificationTitle(__('capell-layout-builder::message.assets_removed_save_layout'))
            ->action(function (self $livewire, array $arguments, Action $action): void {
                $selectedAssets = $livewire->getSelectedAssets($arguments['containerKey'], $arguments['widgetIndex']);

                if ($selectedAssets === []) {
                    Notification::make('no-assets-selected')
                        ->body(__('capell-layout-builder::message.no_assets_selected'))
                        ->warning()
                        ->send();

                    $action->halt();
                }

                $livewire->removeSelectedAssets($arguments['containerKey'], $arguments['widgetIndex']);

                $action->success();
            });
    }

    public function changeLayoutAction(): Action
    {
        return Action::make('changeLayout')
            ->label(__('capell-admin::button.change'))
            ->tooltip(__('capell-layout-builder::button.change_layout'))
            ->button()
            ->size(Size::ExtraSmall)
            ->icon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->modalHeading(__('capell-layout-builder::button.change_layout'))
            ->modalWidth(Width::Small)
            ->visible(fn (): bool => $this->inPageContext())
            ->schema(
                fn (Schema $schema, self $livewire): Schema => $schema->operation('editOption')
                    ->schema($livewire->getChangeLayoutSchema()),
            )
            ->fillForm(fn (self $livewire): array => ['layout_id' => $livewire->layout->getKey()])
            ->modalSubmitActionLabel(__('capell-layout-builder::button.change_layout'))
            ->action(function (self $livewire, Action $action, array $data): void {
                $livewire->changePageLayout($data['layout_id']);

                $this->dispatch('page-layout-changed', id: $data['layout_id']);

                $action->success();
            });
    }

    public function togglePageAssetsAction(): Action
    {
        return Action::make('togglePageAssets')
            ->label(
                function (self $livewire, array $arguments): string {
                    $hasPageAssets = $livewire->hasPageAssets(
                        containerKey: $arguments['containerKey'],
                        widgetIndex: $arguments['widgetIndex'],
                    );

                    return $hasPageAssets
                        ? __('capell-layout-builder::button.convert_widget_assets')
                        : __('capell-layout-builder::button.convert_page_assets');
                },
            )
            ->grouped()
            ->icon('heroicon-o-arrows-right-left')
            ->color('warning')
            ->size(Size::ExtraSmall)
            ->visible(function (self $livewire, array $arguments): bool {
                if (! $livewire->inPageContext()) {
                    return false;
                }

                $this->ensureLoaded();

                $widget = $livewire->getContainerWidget($arguments['containerKey'], $arguments['widgetIndex']);

                $assetTypes = isset($widget->admin['asset_types']) && $widget->admin['asset_types'] !== []
                    ? $widget->admin['asset_types']
                    : ($widget->type->admin['asset_types'] ?? null);

                if ($assetTypes === null) {
                    return false;
                }

                $assets = $livewire->getWidgetAssets(
                    $arguments['containerKey'],
                    $arguments['widgetIndex'],
                );

                if ($assets === []) {
                    return false;
                }

                $hasPageAssets = $livewire->widgetHasPageAssets($widget);

                $hasGlobalAssets = $livewire->widgetHasGlobalAssets($widget);

                return ! $hasPageAssets || ! $hasGlobalAssets;
            })
            ->requiresConfirmation()
            ->modalDescription(
                function (self $livewire, array $arguments): string {
                    $hasPageAssets = $livewire->hasPageAssets(
                        containerKey: $arguments['containerKey'],
                        widgetIndex: $arguments['widgetIndex'],
                    );

                    return $hasPageAssets
                        ? __('capell-admin::generic.convert_widget_assets')
                        : __('capell-admin::generic.convert_page_assets');
                },
            )
            ->action(function (self $livewire, array $arguments, Action $action): void {
                $this->ensureLoaded();

                $hasPageAssets = $livewire->hasPageAssets(
                    containerKey: $arguments['containerKey'],
                    widgetIndex: $arguments['widgetIndex'],
                );

                $livewire->togglePageAssets(
                    $arguments['containerKey'],
                    $arguments['widgetIndex'],
                    page: $hasPageAssets ? $livewire->page : null,
                );

                $action->success();
            });
    }

    protected function addAssetFromAction(Action $action, array $arguments, array $data): void
    {
        $this->assertCanUpdateLayout();

        $this->loadFromStore();

        $configurator = $this->getMountedActionSchema();

        throw_unless($configurator instanceof Schema, Exception::class, 'Mounted action schema not found.');

        $configurator->livewire($this);

        $containerKey = $arguments['containerKey'];
        $widgetIndex = $arguments['widgetIndex'];
        $type = $arguments['type'];

        $hasPageAssets = $this->shouldAddPageAssets($containerKey, $widgetIndex);

        $widget = $this->getContainerWidget($containerKey, $widgetIndex);

        $order = $this->countWidgetAssets($containerKey, $widgetIndex) + 1;

        /** @var WidgetAsset $widgetAsset */
        $widgetAsset = $configurator->getRecord();

        // Fake exists to ensure assets relations are saved correctly
        $widgetAsset->exists = true;
        $widgetAsset->wasRecentlyCreated = true; // prevent MissingAttributeException

        $data['widget_id'] = $widget->id;

        // Ensure UpdatedModelAction is not triggered
        WidgetAsset::withoutEvents(function () use ($configurator): void {
            $configurator->saveRelationships();
        });

        if (! isset($this->assets[$containerKey][$widgetIndex])) {
            $this->assets[$containerKey][$widgetIndex] = [];
        }

        $assetId = $widgetAsset->asset_id;

        $widget = $this->getContainerWidget($containerKey, $widgetIndex);

        $occurrence = $this->getContainerWidgetOccurrence($containerKey, $widgetIndex);

        $meta = $data[$assetId] ?? [];

        $asset = [
            'asset_id' => $assetId,
            'asset_type' => $type,
            'meta' => $meta,
            'widget_id' => $widget->id,
            'order' => $order,
            'occurrence' => $occurrence,
        ];

        if ($hasPageAssets) {
            $asset['pageable_id'] = $this->page->getKey();
            $asset['pageable_type'] = $this->page->getMorphClass();
            $asset['container'] = $containerKey;
        }

        $this->assets[$containerKey][$widgetIndex][] = $asset;

        $widgetAsset->load([
            'asset' => fn (MorphTo $query): MorphTo => $query->morphWith($this->getAssetRelations()),
        ]);

        $widgetAsset->setRelation('widget', $widget);

        $widget->assets->add($widgetAsset);

        $this->layoutUpdated();

        $action->success();

        $this->dispatch(
            'refresh-assets',
            containerKey: $containerKey,
            widgetIndex: $widgetIndex,
        );
    }

    protected function duplicateLayout(): void
    {
        $this->assertCanUpdateLayout();

        $newLayout = ReplicateLayoutAction::run($this->layout);

        $this->dispatch('page-layout-changed', id: $newLayout->getKey());
    }

    protected function changePageLayout(int $layoutId): void
    {
        $this->assertCanUpdateLayout();

        if (! $this->inPageContext()) {
            return;
        }

        $this->layoutUpdated();

        $this->dispatch('page-layout-changed', id: $layoutId);
    }

    protected function makeWidgetAssetRecordForCreate(array $arguments): WidgetAsset
    {
        $containerKey = $arguments['containerKey'];
        $widgetIndex = $arguments['widgetIndex'];
        $assetType = $arguments['type'];

        $widget = $this->getContainerWidget($containerKey, $widgetIndex);

        /** @var class-string<WidgetAsset> $model */
        $model = WidgetAsset::class;

        $record = $model::query()->make([
            'widget_id' => $widget->id,
            'workspace_id' => $this->getCurrentWidgetAssetWorkspaceId($widget),
            'asset_type' => $assetType,
            'meta' => [],
        ]);

        $asset = CapellCore::getAsset($assetType)->model::make();

        $record->setRelation('asset', $asset);

        return $record;
    }

    protected function resolveEditableWidgetAsset(array $arguments): WidgetAsset
    {
        $containerKey = $arguments['containerKey'];
        $widgetIndex = $arguments['widgetIndex'];
        $index = $arguments['index'];
        $type = $arguments['type'];

        $widget = $this->getContainerWidget($containerKey, $widgetIndex);
        $asset = $this->getWidgetAsset($containerKey, $widgetIndex, $index);

        throw_unless($asset, MissingWidgetAssetException::class, $widget, $type, $index, $arguments);

        $assetId = $asset['asset_id'];

        $widgetAsset = isset($asset['id'])
            ? $widget->assets->first(fn (WidgetAsset $widgetAsset): bool => (int) $widgetAsset->getKey() === (int) $asset['id'])
            : null;

        $widgetAsset ??= $widget->assets
            ->where('asset_type', $type)
            ->where('asset_id', $assetId)
            ->first();

        throw_unless($widgetAsset, Exception::class, sprintf('Asset of type [%s] with ID [%s] not found.', $type, $assetId));
        throw_unless((int) $widgetAsset->widget_id === (int) $widget->getKey(), Exception::class, sprintf('Asset of type [%s] with ID [%s] is not attached to this widget.', $type, $assetId));

        return $widgetAsset;
    }

    protected function getEditWidgetAssetModalHeading(self $livewire, array $arguments): string
    {
        $name = str($arguments['type'])->title();

        if ($livewire->inPageContext()) {
            return __('capell-layout-builder::heading.edit_page_widget_asset', ['name' => $name]);
        }

        return __('capell-layout-builder::heading.edit_widget_asset', ['name' => $name]);
    }

    protected function getEditWidgetAssetModalDescription(self $livewire, array $arguments): ?string
    {
        if (! $livewire->inPageContext()) {
            return null;
        }

        $widgetAsset = $this->getWidgetAsset($arguments['containerKey'], $arguments['widgetIndex'], $arguments['index']);

        if (! isset($widgetAsset['pageable_id'], $widgetAsset['pageable_type'])) {
            return null;
        }

        return __('capell-layout-builder::heading.page_widget_asset', ['name' => $livewire->page->name]);
    }

    protected function applyWidgetAssetUpdate(WidgetAsset $record, array $data, self $livewire, array $arguments, Action $action, Schema $configurator): void
    {
        $this->assertCanEditContent();

        $this->loadFromStore();

        $expectedSignature = $arguments['contentInventorySignature'] ?? null;

        if (is_string($expectedSignature) && ! hash_equals($expectedSignature, $this->contentInventorySignature())) {
            Notification::make('content-inventory-stale')
                ->title(__('capell-layout-builder::message.content_stale'))
                ->warning()
                ->send();

            $action->halt();
        }

        $widget = $this->getContainerWidget($arguments['containerKey'], $arguments['widgetIndex']);
        $canUpdatePersistedRecord = $record->workspace_id === $this->getCurrentWidgetAssetWorkspaceId($widget);

        if ($canUpdatePersistedRecord) {
            $configurator->saveRelationships();
        }

        if ($data !== [] && $canUpdatePersistedRecord) {
            $record->update($data);
        }

        if (isset($data['meta'])) {
            $livewire->updateWidgetAsset($arguments['containerKey'], $arguments['widgetIndex'], $arguments['index'], ['meta' => $data['meta']]);
        }

        $livewire->reloadContainerWidgetAsset($arguments['containerKey'], $arguments['widgetIndex'], $arguments['index']);

        $action->success();
    }

    protected function getWidgetAssetSchema(Schema $configurator): Schema
    {
        return WidgetAssetForm::configure($configurator);
    }

    protected function getChangeLayoutSchema(): array
    {
        return [
            Select::make('layout_id')
                ->label(__('capell-admin::form.layout'))
                ->required()
                ->searchable()
                ->options(
                    fn (): array => Layout::query()
                        ->withCount('pages')
                        ->when(
                            $this->getSite(),
                            fn (EloquentBuilder $query, Site $site): EloquentBuilder => $query->where(
                                fn (EloquentBuilder $query): EloquentBuilder => $query->where('site_id', $site->getKey())
                                    ->orWhereNull('site_id'),
                            ),
                        )
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(
                            fn (Layout $layout): array => [$layout->id => $layout->name . ' (' . $layout->pages_count . ')'],
                        )
                        ->all(),
                )
                ->default(function () {
                    /** @var class-string<Layout> $model */
                    $model = Layout::class;

                    return $model::query()->default()->first(['id'])?->id;
                })
                ->reactive()
                ->helperText(
                    function (?int $state): ?HtmlString {
                        if ($state === null || $state === 0) {
                            return null;
                        }

                        $layout = Layout::query()
                            ->when(
                                $this->getSite(),
                                fn (EloquentBuilder $query, Site $site): EloquentBuilder => $query->where(
                                    fn (EloquentBuilder $query): EloquentBuilder => $query
                                        ->where('site_id', $site->getKey())
                                        ->orWhereNull('site_id'),
                                ),
                            )
                            ->find($state);

                        if (! $layout instanceof Layout) {
                            return null;
                        }

                        $total = $layout->pages()->count();

                        return new HtmlString(
                            trans_choice(
                                'capell-layout-builder::message.layout_count_on_pages',
                                $total,
                                [
                                    'count' => $total,
                                    'url' => AdminSurfaceLookup::resource(ResourceEnum::Page)::getUrl(
                                        'index',
                                        ['filters' => ['layout_id' => ['value' => $state]]],
                                    ),
                                ],
                            ),
                        );
                    },
                ),
        ];
    }

    protected function saveWidgetForm(Schema $configurator, Widget $record, array $data): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        $configurator->saveRelationships();

        $record->update($data);
    }
}
