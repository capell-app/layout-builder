<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Filament\Concerns;

use BackedEnum;
use Capell\Admin\Actions\ReplicateLayoutAction;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Layout\Enums\LivewireComponentsEnum;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Enums\TypeSchemaEnum;
use Capell\Layout\Exceptions\MissingWidgetAssetException;
use Capell\Layout\Filament\Resources\Widgets\Schemas\WidgetAssetForm;
use Capell\Layout\Filament\Resources\Widgets\Schemas\WidgetForm;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

trait HasLayoutActions
{
    public function saveLayoutAction(): Action
    {
        return Action::make('saveLayout')
            ->label(__('capell-layout::button.save_layout'))
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
            ->label(__('capell-layout::button.copy_layout'))
            ->groupedIcon('heroicon-o-square-2-stack')
            ->modalWidth(Width::ScreenSmall)
            ->requiresConfirmation()
            ->modalDescription(__('capell-layout::message.copy_layout_confirmation'))
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
            ->label(__('capell-layout::button.container'))
            ->tooltip(__('capell-layout::button.add_container'))
            ->icon('heroicon-m-plus')
            ->color('gray')
            ->link()
            ->size(Size::Small)
            ->record(fn (): Layout => $this->layout)
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitActionLabel(fn (Action $action): string => $action->getTooltip())
            ->schema(
                static fn (self $livewire, Schema $schema, array $arguments): Schema => $schema->operation('createOption')
                    ->schema($livewire->getContainerSchema($schema, $arguments)),
            )
            ->action(function (Action $action, self $livewire, array $data): void {
                $livewire->saveContainer($data);

                $action->success();
            });
    }

    public function editContainerAction(): Action
    {
        return Action::make('editContainer')
            ->label(__('capell-layout::button.edit_container'))
            ->groupedIcon('heroicon-o-pencil')
            ->size(Size::Small)
            ->color('gray')
            ->grouped()
            ->record(fn (): Layout => $this->layout)
            ->modalWidth(Width::ScreenLarge)
            ->modalHeading(
                fn (array $arguments): string|array|null => __(
                    'capell-layout::heading.edit_container',
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
            ->label(__('capell-layout::button.remove_container'))
            ->groupedIcon('heroicon-m-trash')
            ->color('danger')
            ->size(Size::Small)
            ->grouped()
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->removeContainer($arguments['containerKey']);

                $action->success();
            });
    }

    public function editLayoutWidgetAction(): Action
    {
        return Action::make('editLayoutWidget')
            ->label(__('capell-layout::button.edit_layout_widget'))
            ->groupedIcon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->grouped()
            ->visible(
                fn (array $arguments, self $livewire): bool => (bool) $livewire->getContainerWidgetSchema(
                    $arguments['containerKey'],
                    $arguments['widgetIndex'],
                ),
            )
            ->modalHeading(__('capell-layout::heading.container_widget_settings'))
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
                $adminSchema = CapellAdmin::getSchema(
                    TypeSchemaEnum::LayoutWidget->value,
                    $livewire->getContainerWidgetSchema($arguments['containerKey'], $arguments['widgetIndex']),
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
            ->label(__('capell-layout::button.widget'))
            ->tooltip(__('capell-layout::button.add_widget'))
            ->modalHeading(__('capell-layout::heading.add_widget_to_container'))
            ->icon('heroicon-c-plus')
            ->size(Size::Small)
            ->color('gray')
            ->link()
            ->visible(fn (): bool => (bool) $this->containers)
            ->modalWidth(Width::ScreenLarge)
            ->extraModalWindowAttributes([
                'class' => 'capell-layout-builder-assets-table',
            ])
            ->modalContent(function (Action $action, array $arguments): HtmlString {
                /** @var self $livewire */
                $livewire = $action->getLivewire();

                return new HtmlString(Blade::render(
                    <<<'blade'
                       @livewire($component, [
                           'actionModalId' => $actionModalId,
                           'containerKey' => $containerKey,
                           'containers' => $containers,
                       ], key($livewireKey))
                   blade,
                    [
                        'actionModalId' => sprintf('fi-%s-action-%s', $livewire->getId(), $action->getNestingIndex()),
                        'containerKey' => $arguments['containerKey'] ?? '',
                        'component' => LivewireComponentsEnum::WidgetTableSelect->value,
                        'livewireKey' => sprintf('fi-%s-action-%s-widgets-table', $livewire->getId(), $action->getNestingIndex()),
                        'containers' => self::getContainerOptions(),
                    ],
                ));
            })
            ->formWrapper(false)
            ->closeModalByClickingAway(false)
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->action(null)
            ->submit(null);
    }

    public function editWidgetAction(): Action
    {
        return Action::make('editWidget')
            ->label(__('capell-layout::button.edit_widget'))
            ->tooltip(__('capell-layout::button.edit_widget'))
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
                    'capell-layout::heading.widget_type',
                    ['type' => $record->type?->name],
                ),
            )
            ->modalSubmitActionLabel(__('capell-layout::button.save_changes'))
            ->successNotificationTitle(__('capell-layout::message.widget_updated'))
            ->fillForm(fn (Widget $record): array => $record->attributesToArray())
            ->schema(
                fn (Action $action, Schema $schema): Schema => WidgetForm::configure(
                    $schema->operation('editOption')
                        ->record(fn (): Widget => $action->getRecord()->fresh()),
                ),
            )
            ->action(function (Action $action, Widget $record, Schema $schema, array $data): void {
                $this->saveWidgetForm(schema: $schema, record: $record, data: $data);

                $action->success();
            });
    }

    public function duplicateWidgetAction(): Action
    {
        return Action::make('duplicateWidget')
            ->label(__('capell-layout::button.duplicate_widget'))
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
            ->label(__('capell-layout::button.remove_widget'))
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
                    'capell-layout::button.select_asset',
                    ['asset' => CapellCore::getAsset($arguments['type'])->getLabel()],
                ),
            )
            ->grouped()
            ->modal()
            ->icon('heroicon-c-magnifying-glass')
            ->iconSize(IconSize::Small)
            ->size(Size::Small)
            ->extraModalWindowAttributes([
                'class' => 'capell-layout-builder-assets-table',
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
            ->modalContent(function (Action $action, array $arguments): HtmlString {
                /** @var self $livewire */
                $livewire = $action->getLivewire();

                $component = LivewireComponentsEnum::loadAssetComponent($arguments['type'])->value;

                $existingRecords = $livewire->getWidgetAssetsByType(
                    $arguments['containerKey'],
                    $arguments['widgetIndex'],
                    $arguments['type'],
                );

                return new HtmlString(Blade::render(
                    <<<'blade'
                       @livewire($component, [
                           'actionModalId' => $actionModalId,
                           'tableArguments' => $arguments,
                           'existingRecords' => $existingRecords,
                       ], key($actionModalId))
                   blade,
                    [
                        'actionModalId' => sprintf('fi-%s-action-%s', $livewire->getId(), $action->getNestingIndex()),
                        'arguments' => [
                            'containerKey' => $arguments['containerKey'],
                            'widgetIndex' => $arguments['widgetIndex'],
                            'pageableId' => $livewire->page?->getKey(),
                            'pageableType' => $livewire->page?->getMorphClass(),
                            'siteId' => $livewire->site?->getKey(),
                        ],
                        'component' => $component,
                        'existingRecords' => $existingRecords,
                    ],
                ));
            })
            ->formWrapper(false)
            ->closeModalByClickingAway(false)
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->action(null)
            ->submit(null);
    }

    public function addAssetAction(): Action
    {
        return Action::make('addAsset')
            ->label(
                fn (array $arguments): string => __(
                    'capell-layout::button.add_new_asset',
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
                    'capell-layout::button.create_widget_asset',
                    ['type' => $arguments['type']],
                ),
            )
            ->successNotificationTitle(__('capell-layout::message.asset_added'))
            ->schema(
                fn (array $arguments, Schema $schema): Schema => self::getWidgetAssetSchema(
                    $schema->operation('createOption')
                        ->record(fn (): WidgetAsset => $this->makeWidgetAssetRecordForCreate($arguments)),
                ),
            )
            ->model(fn (): string => CapellCore::getModel(ModelEnum::WidgetAsset->name))
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
                    'capell-layout::button.edit_asset_type',
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
            ->modalSubmitActionLabel(__('capell-layout::button.save_changes'))
            ->successNotificationTitle(__('capell-layout::message.asset_updated'))
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
                    schema: $schema,
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
                        ->body(__('capell-layout::message.no_assets_selected'))
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
            ->tooltip(__('capell-layout::button.change_layout'))
            ->button()
            ->size(Size::ExtraSmall)
            ->icon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->modalHeading(__('capell-layout::button.change_layout'))
            ->modalWidth(Width::Small)
            ->visible(fn (): bool => $this->inPageContext())
            ->schema(
                fn (Schema $schema, self $livewire): Schema => $schema->operation('editOption')
                    ->schema($livewire->getChangeLayoutSchema()),
            )
            ->fillForm(fn (self $livewire): array => ['layout_id' => $livewire->layout->getKey()])
            ->modalSubmitActionLabel(__('capell-layout::button.change_layout'))
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
                        ? __('capell-layout::button.convert_widget_assets')
                        : __('capell-layout::button.convert_page_assets');
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

    protected function addAssetFromAction(Action $action, Schema $schema, array $arguments, array $data): void
    {
        $this->loadFromStore();

        $containerKey = $arguments['containerKey'];
        $widgetIndex = $arguments['widgetIndex'];
        $type = $arguments['type'];

        $hasPageAssets = $this->shouldAddPageAssets($containerKey, $widgetIndex);

        $widget = $this->getContainerWidget($containerKey, $widgetIndex);

        $order = $this->countWidgetAssets($containerKey, $widgetIndex) + 1;

        /** @var WidgetAsset $widgetAsset */
        $widgetAsset = $schema->getRecord();

        // Fake exists to ensure assets relations are saved correctly
        $widgetAsset->exists = true;
        $widgetAsset->wasRecentlyCreated = true; // prevent MissingAttributeException

        $data['widget_id'] = $widget->id;

        // Ensure UpdatedModelAction is not triggered
        WidgetAsset::withoutEvents(function () use ($schema): void {
            $schema->saveRelationships();
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
        $newLayout = ReplicateLayoutAction::run($this->layout);

        $this->dispatch('page-layout-changed', id: $newLayout->getKey());
    }

    protected function changePageLayout(int $layoutId): void
    {
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
        $model = CapellCore::getModel(ModelEnum::WidgetAsset->name);

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
            return __('capell-layout::heading.edit_page_widget_asset', ['name' => $name]);
        }

        return __('capell-layout::heading.edit_widget_asset', ['name' => $name]);
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

        return __('capell-layout::heading.page_widget_asset', ['name' => $livewire->page->name]);
    }

    protected function applyWidgetAssetUpdate(WidgetAsset $record, array $data, self $livewire, array $arguments, Action $action, Schema $schema): void
    {
        $this->loadFromStore();

        $schema->saveRelationships();

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

    protected function getWidgetAssetSchema(Schema $schema): Schema
    {
        return WidgetAssetForm::configure($schema);
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
                    $model = CapellCore::getModel(\Capell\Core\Enums\ModelEnum::Layout);

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
                                'capell-layout::message.layout_count_on_pages',
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

    protected function saveWidgetForm(Schema $schema, Widget $record, array $data): void
    {
        $this->ensureLoaded();

        $schema->saveRelationships();

        $record->update($data);
    }
}
