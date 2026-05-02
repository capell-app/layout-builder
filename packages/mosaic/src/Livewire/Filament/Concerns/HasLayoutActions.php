<?php

declare(strict_types=1);

namespace Capell\Mosaic\Livewire\Filament\Concerns;

use BackedEnum;
use Capell\Admin\Actions\ReplicateLayoutAction;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Mosaic\Enums\ConfiguratorTypeEnum;
use Capell\Mosaic\Exceptions\MissingWidgetAssetException;
use Capell\Mosaic\Filament\Resources\Pages\Tables\PageSelectionTable;
use Capell\Mosaic\Filament\Resources\Sections\Tables\SectionSelectionTable;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\WidgetAssetForm;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\WidgetForm;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Models\WidgetAsset;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TableSelect;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\HtmlString;

trait HasLayoutActions
{
    public function saveLayoutAction(): Action
    {
        return Action::make('saveLayout')
            ->label(__('capell-mosaic::button.save_layout'))
            ->color('primary')
            ->size(Size::Small)
            ->link()
            ->action(function (Action $action, self $livewire): void {
                $livewire->saveLayout(withNotifications: true);

                $action->success();
            });
    }

    public function duplicateLayoutAction(): Action
    {
        return Action::make('duplicateLayout')
            ->label(__('capell-mosaic::button.copy_layout'))
            ->groupedIcon('heroicon-o-square-2-stack')
            ->modalWidth(Width::ScreenSmall)
            ->requiresConfirmation()
            ->modalDescription(__('capell-mosaic::message.copy_layout_confirmation'))
            ->visible(fn (): bool => $this->inPageContext())
            ->action(function (Action $action, self $livewire): void {
                $livewire->duplicateLayout();

                $livewire->layoutUpdated();

                $action->success();
            });
    }

    public function addContainerAction(): Action
    {
        return Action::make('addContainer')
            ->label(__('capell-mosaic::button.container'))
            ->tooltip(__('capell-mosaic::button.add_container'))
            ->icon('heroicon-m-plus')
            ->color('gray')
            ->link()
            ->size(Size::Small)
            ->record(fn (): Layout => $this->layout)
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitActionLabel(fn (Action $action): string => $action->getTooltip())
            ->schema(
                static fn (self $livewire, Schema $configurator, array $arguments): Schema => $configurator->operation('createOption')
                    ->schema($livewire->getContainerSchema($configurator, $arguments)),
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
            ->label(__('capell-mosaic::button.edit_container'))
            ->groupedIcon('heroicon-o-pencil')
            ->size(Size::Small)
            ->color('gray')
            ->grouped()
            ->record(fn (): Layout => $this->layout)
            ->modalWidth(Width::ScreenLarge)
            ->modalHeading(
                fn (array $arguments): string|array|null => __(
                    'capell-mosaic::heading.edit_container',
                    ['key' => str($arguments['containerKey'])->title()],
                ),
            )
            ->modalSubmitActionLabel(fn (Action $action): string => $action->getLabel())
            ->schema(
                static fn (self $livewire, Schema $configurator, array $arguments): Schema => $configurator->operation('editOption')
                    ->schema($livewire->getContainerSchema($configurator, $arguments)),
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
            ->label(__('capell-mosaic::button.remove_container'))
            ->groupedIcon('heroicon-m-trash')
            ->color('danger')
            ->size(Size::Small)
            ->grouped()
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->removeContainer($arguments['containerKey']);

                $action->success();
            });
    }

    public function duplicateContainerAction(): Action
    {
        return Action::make('duplicateContainer')
            ->label(__('capell-mosaic::button.duplicate_container'))
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
            ->label(__('capell-mosaic::button.edit_layout_widget'))
            ->groupedIcon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->grouped()
            ->visible(
                fn (array $arguments, self $livewire): bool => (bool) $livewire->getContainerWidgetConfigurator(
                    $arguments['containerKey'],
                    $arguments['widgetIndex'],
                ),
            )
            ->modalHeading(__('capell-mosaic::heading.container_widget_settings'))
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
            ->schema(function (array $arguments, self $livewire, Schema $configurator): Schema {
                $adminSchema = CapellAdmin::getConfigurator(
                    ConfiguratorTypeEnum::LayoutWidget->value,
                    $livewire->getContainerWidgetConfigurator($arguments['containerKey'], $arguments['widgetIndex']),
                );

                $typeSchema = resolve($adminSchema)->make($configurator);

                return $configurator->operation('editOption')->components($typeSchema);
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
            ->label(__('capell-mosaic::button.widget'))
            ->tooltip(__('capell-mosaic::button.add_widget'))
            ->modalHeading(__('capell-mosaic::heading.add_widget_to_container'))
            ->icon('heroicon-c-plus')
            ->size(Size::Small)
            ->color('gray')
            ->link()
            ->visible(fn (): bool => (bool) $this->containers)
            ->modalWidth(Width::ScreenLarge)
            ->extraModalWindowAttributes([
                'class' => 'capell-mosaic-builder-assets-table',
            ])
            ->closeModalByClickingAway(false)
            ->schema(function (Schema $configurator, array $arguments, self $livewire): Schema {
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

                $components[] = Select::make('widgets')
                    ->label(__('capell-mosaic::button.widget'))
                    ->options(
                        $livewire->getWidgetQuery(withRelations: false)
                            ->ordered()
                            ->pluck('name', 'id')
                            ->all(),
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required();

                return $configurator->schema($components);
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
                );
            });
    }

    public function editWidgetAction(): Action
    {
        return Action::make('editWidget')
            ->label(__('capell-mosaic::button.edit_widget'))
            ->tooltip(__('capell-mosaic::button.edit_widget'))
            ->button()
            ->slideOver()
            ->closeModalByClickingAway(false)
            ->icon('heroicon-o-pencil')
            ->color('gray')
            ->size(Size::ExtraSmall)
            ->modalWidth(Width::ScreenLarge)
            ->hiddenLabel()
            ->record(
                fn (array $arguments): Widget => $this->getContainerWidget(
                    $arguments['containerKey'],
                    $arguments['widgetIndex'],
                ),
            )
            ->modalHeading(fn (Widget $record): string => $record->name)
            ->modalDescription(
                fn (Widget $record): string => __(
                    'capell-mosaic::heading.widget_type',
                    ['type' => $record->type?->name],
                ),
            )
            ->modalSubmitActionLabel(__('capell-mosaic::button.save_changes'))
            ->successNotificationTitle(__('capell-mosaic::message.widget_updated'))
            ->fillForm(fn (Widget $record): array => $record->attributesToArray())
            ->schema(
                fn (Action $action, Schema $configurator): Schema => WidgetForm::configure(
                    $configurator->operation('editOption')
                        ->record(fn (): Widget => $action->getRecord()->fresh()),
                ),
            )
            ->action(function (Action $action, Widget $record, Schema $configurator, array $data): void {
                $this->saveWidgetForm(configurator: $configurator, record: $record, data: $data);

                $action->success();
            });
    }

    public function duplicateWidgetAction(): Action
    {
        return Action::make('duplicateWidget')
            ->label(__('capell-mosaic::button.duplicate_widget'))
            ->grouped()
            ->groupedIcon('heroicon-o-square-2-stack')
            ->color('gray')
            ->size('sm')
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->duplicateWidget(containerKey: $arguments['containerKey'], originalIndex: $arguments['widgetIndex']);

                $action->success();
            });
    }

    public function removeWidgetAction(): Action
    {
        return Action::make('removeWidget')
            ->label(__('capell-mosaic::button.remove_widget'))
            ->grouped()
            ->groupedIcon('heroicon-m-trash')
            ->color('danger')
            ->size('sm')
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
                    'capell-mosaic::button.select_asset',
                    ['asset' => CapellCore::getAsset($arguments['type'])->getLabel()],
                ),
            )
            ->grouped()
            ->modal()
            ->icon('heroicon-c-magnifying-glass')
            ->iconSize(IconSize::Small)
            ->size(Size::Small)
            ->extraModalWindowAttributes([
                'class' => 'capell-mosaic-builder-assets-table',
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
            ->schema(function (Schema $configurator, array $arguments, self $livewire): Schema {
                $tableConfiguration = match ($arguments['type']) {
                    'page' => PageSelectionTable::class,
                    default => SectionSelectionTable::class,
                };

                $excludeIds = $livewire->getWidgetAssetsByType(
                    $arguments['containerKey'],
                    (int) $arguments['widgetIndex'],
                    $arguments['type'],
                );

                return $configurator->schema([
                    TableSelect::make('assets')
                        ->tableConfiguration($tableConfiguration)
                        ->multiple()
                        ->hiddenLabel()
                        ->tableArguments([
                            'excludeIds' => $excludeIds,
                            'pageId' => $livewire->page?->getKey(),
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
                    'capell-mosaic::button.add_new_asset',
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
                    'capell-mosaic::button.create_widget_asset',
                    ['type' => $arguments['type']],
                ),
            )
            ->successNotificationTitle(__('capell-mosaic::message.asset_added'))
            ->schema(
                fn (array $arguments, Schema $configurator): Schema => self::getWidgetAssetSchema(
                    $configurator->operation('createOption')
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
            ->icon(fn (array $arguments): string|BackedEnum => CapellCore::getAsset($arguments['type'])->getIcon())
            ->iconSize(IconSize::Small)
            ->tooltip(
                fn (array $arguments): string => __(
                    'capell-mosaic::button.edit_asset_type',
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
            ->modalSubmitActionLabel(__('capell-mosaic::button.save_changes'))
            ->successNotificationTitle(__('capell-mosaic::message.asset_updated'))
            ->schema(
                fn (self $livewire, Schema $configurator, array $arguments): Schema => self::getWidgetAssetSchema(
                    $configurator->operation('editOption'),
                ),
            )
            ->fillForm(fn (WidgetAsset $record, array $arguments): array => [
                'meta' => $record->meta,
            ])
            ->record(fn (array $arguments): WidgetAsset => $this->resolveEditableWidgetAsset($arguments))
            ->disabled(fn (WidgetAsset $record): bool => ! $record->exists)
            ->action(
                fn (WidgetAsset $record, array $data, self $livewire, array $arguments, Action $action, Schema $configurator) => $this->applyWidgetAssetUpdate(
                    record: $record,
                    data: $data,
                    livewire: $livewire,
                    arguments: $arguments,
                    action: $action,
                    configurator: $configurator,
                ),
            );
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
            ->successNotificationTitle(__('Assets removed successfully. Save the layout to apply changes.'))
            ->action(function (self $livewire, array $arguments, Action $action): void {
                $selectedAssets = $livewire->getSelectedAssets($arguments['containerKey'], $arguments['widgetIndex']);

                if ($selectedAssets === []) {
                    Notification::make('no-assets-selected')
                        ->body(__('capell-mosaic::message.no_assets_selected'))
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
            ->tooltip(__('capell-mosaic::button.change_layout'))
            ->button()
            ->size(Size::ExtraSmall)
            ->icon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->modalHeading(__('capell-mosaic::button.change_layout'))
            ->modalWidth(Width::Small)
            ->visible(fn (): bool => $this->inPageContext())
            ->schema(
                fn (Schema $configurator, self $livewire): Schema => $configurator->operation('editOption')
                    ->schema($livewire->getChangeLayoutSchema()),
            )
            ->fillForm(fn (self $livewire): array => ['layout_id' => $livewire->layout->getKey()])
            ->modalSubmitActionLabel(__('capell-mosaic::button.change_layout'))
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
                        ? __('capell-mosaic::button.convert_widget_assets')
                        : __('capell-mosaic::button.convert_page_assets');
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
            'asset' => fn (BuilderContract $query): BuilderContract => $query->morphWith($this->getAssetRelations()),
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

        $widgetAsset = $widget->assets
            ->where('asset_type', $type)
            ->where('asset_id', $assetId)
            ->first();

        throw_unless($widgetAsset, Exception::class, sprintf('Asset of type [%s] with ID [%s] not found.', $type, $assetId));

        return $widgetAsset;
    }

    protected function getEditWidgetAssetModalHeading(self $livewire, array $arguments): string
    {
        $name = str($arguments['type'])->title();

        if ($livewire->inPageContext()) {
            return __('capell-mosaic::heading.edit_page_widget_asset', ['name' => $name]);
        }

        return __('capell-mosaic::heading.edit_widget_asset', ['name' => $name]);
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

        return __('capell-mosaic::heading.page_widget_asset', ['name' => $livewire->page->name]);
    }

    protected function applyWidgetAssetUpdate(WidgetAsset $record, array $data, self $livewire, array $arguments, Action $action, Schema $configurator): void
    {
        $this->assertCanUpdateLayout();

        $this->loadFromStore();

        $configurator->saveRelationships();

        if ($data !== []) {
            $record->update($data);
        }

        if (isset($data['meta'])) {
            $livewire->updateWidgetAsset($arguments['containerKey'], $arguments['widgetIndex'], $arguments['index'], ['meta' => $data['meta']]);
        }

        $livewire->reloadContainerWidgetAsset($arguments['containerKey'], $arguments['widgetIndex'], $arguments['index']);

        $livewire->notifyPageCached($record);

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
                            $this->site,
                            fn (EloquentBuilder $query, ?Site $site): EloquentBuilder => $query->where(
                                fn (EloquentBuilder $query) => $query->where('site_id', $site->getKey())
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

                        $total = Layout::query()->find($state)->pages()->count();

                        return new HtmlString(
                            trans_choice(
                                'capell-mosaic::message.layout_count_on_pages',
                                $total,
                                [
                                    'count' => $total,
                                    'url' => CapellAdmin::getResource(ResourceEnum::Page)::getUrl(
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
