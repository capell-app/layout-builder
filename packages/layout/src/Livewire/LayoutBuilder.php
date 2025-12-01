<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire;

use BackedEnum;
use Capell\Admin\Actions\NotifyClearCachedPagesAction;
use Capell\Admin\Actions\ReplicateLayoutAction;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Concerns\HasPageCacheNotification;
use Capell\Core\Actions\GetResourceFromTypeAction;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Enums\SchemaTypeEnum;
use Capell\Layout\Exceptions\MissingWidgetAssetException;
use Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Containers\DefaultLayoutContainerSchema;
use Capell\Layout\Filament\Resources\Widgets\Schemas\WidgetAssetForm;
use Capell\Layout\Filament\Resources\Widgets\Schemas\WidgetForm;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Closure;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property-read ?Page $page
 */
class LayoutBuilder extends Component implements HasActions, HasForms
{
    use HasPageCacheNotification;
    use InteractsWithActions;
    use InteractsWithForms;

    #[Locked]
    public ?int $page_id = null;

    #[Locked]
    public ?int $site_id = null;

    #[Locked]
    public int $layout_id;

    public ?array $containers = null;

    public ?array $assets = null;

    public array $selectedRecords;

    public bool $layoutModified = false;

    protected array $containerWidgets;

    protected Layout $layoutRecord;

    protected ?Page $layoutPage = null;

    protected ?Site $site = null;

    private string $view = 'capell-layout::livewire.layout-builder';

    public function mount(int|string|null $record = null): void
    {
        if ($record !== null && ! isset($this->layout_id)) {
            $this->layout_id = (int) $record; // allow Filament style mount passing record id
        }

        $this->loadNew();
    }

    public function boot(): void
    {
        throw_if(! Filament::auth()->check(), AuthenticationException::class);
    }

    #[On('save-layout')]
    public function saveLayout(bool $withNotifications = false): void
    {
        if (! $this->layoutModified) {
            return;
        }

        $this->loadFromStore();

        $originalContainers = $this->layoutRecord->containers ?? [];

        $this->layoutRecord->update([
            'containers' => $this->containers,
        ]);

        if ($this->page_id && $this->getLayoutPage()->layout_id !== $this->layout_id) {
            $this->layoutPage->update([
                'layout_id' => $this->layout_id,
            ]);
        }

        $processedWidgetKeys = [];

        foreach ($this->containers as $containerKey => $container) {
            foreach ($container['widgets'] as $widgetIndex => $widget) {
                if ($this->inPageContext() && ! empty($widget['page_id'])) {
                    $key = $widget['widget_key'] . '_' . $widget['page_id'] . '_' . $widget['container'] . '_' . $widget['occurrence'];
                } else {
                    $key = $widget['widget_key'] . '_' . $widget['occurrence'];
                }

                if (in_array($key, $processedWidgetKeys, true)) {
                    continue;
                }

                $processedWidgetKeys[] = $key;

                $this->updateAssets($containerKey, $widgetIndex, $widget['old_container'] ?? null);
            }
        }

        if ($this->inPageContext()) {
            $this->cleanUpWidgetAssets($originalContainers);
        }

        $this->dispatch('layout-builder-reset');

        $this->layoutUpdated(false);

        if ($withNotifications) {
            Notification::make('layout-saved')
                ->body(__('capell-layout::message.layout_saved'))
                ->success()
                ->send();

            NotifyClearCachedPagesAction::run(
                collect([$this->getLayoutRecord()])
                    ->when(
                        $this->getLayoutPage(),
                        fn (SupportCollection $collection, $page): SupportCollection => $collection->push($page),
                    ),
            );
        }
    }

    #[On('add-widgets-to-container')]
    public function addWidgetsToContainer(string $containerKey, array $widgets): void
    {
        if (! isset($this->layoutRecord)) {
            $this->loadFromStore();
        }

        foreach ($widgets as $widgetId) {
            $widget = $this->getWidget($widgetId);

            $widgetIndex = $this->addWidgetToContainer($widget, $containerKey);

            $widget = $this->loadWidget($containerKey, $widgetIndex);

            $this->assets[$containerKey][$widgetIndex] = $this->mapWidgetAssets($widget, $containerKey);

            $this->updatePageAssets($containerKey, $widgetIndex);
        }

        session(['layout-builder.container' => $containerKey]);

        $this->setupSelectedAssets();

        $this->layoutUpdated();
    }

    #[On('sync-selected-assets')]
    public function addAssetsToWidget(array $arguments, string $type, array $assets): void
    {
        if (! isset($this->layoutRecord)) {
            $this->loadFromStore();
        }

        $containerKey = $arguments['containerKey'];
        $widgetIndex = $arguments['widgetIndex'];
        $hasPageAssets = $arguments['hasPageAssets'] ?? false;

        $this->addAssets($containerKey, $widgetIndex, $hasPageAssets, $type, $assets);

        $this->layoutUpdated();
    }

    public function reorderContainers(string $containerKey, int $position): void
    {
        $containers = $this->containers;

        $container = $containers[$containerKey];

        unset($containers[$containerKey]);

        $containers = array_slice($containers, 0, $position, true) +
            [$containerKey => $container] +
            array_slice($containers, $position, null, true);

        $this->containers = $containers;

        $this->layoutUpdated();
    }

    public function reorderWidgets(string $containerKey, string $containerWidgetIndex, int $widgetIndex): void
    {
        [$originalContainer, $originalIndex] = explode('.', $containerWidgetIndex);

        $this->moveContainerWidgetAssets($originalContainer, (int) $originalIndex, $containerKey, $widgetIndex);

        $this->moveContainerWidget($originalContainer, (int) $originalIndex, $containerKey, $widgetIndex);

        $this->layoutUpdated();
    }

    public function reorderAssets(string $containerKey, int $widgetIndex, int $index, int $newIndex): void
    {
        $assets = $this->assets[$containerKey][$widgetIndex];

        $widgetAsset = $this->getWidgetAsset($containerKey, $widgetIndex, $index);

        throw_if($widgetAsset === null || $widgetAsset === [], Exception::class, sprintf('Asset %d not found for container: %s widget: %d', $index, $containerKey, $widgetIndex));

        unset($assets[$index]);

        $assets = array_values($assets);

        array_splice($assets, $newIndex, 0, [$widgetAsset]);

        $order = 1;
        $assets = array_map(
            function (array $asset) use (&$order): array {
                $asset['order'] = $order;
                $order++;

                return $asset;
            },
            $assets,
        );

        $this->assets[$containerKey][$widgetIndex] = $assets;

        $this->layoutUpdated();
    }

    public function saveLayoutAction(): Action
    {
        return Action::make('saveLayout')
            ->label(__('capell-layout::button.save_layout'))
            ->color('primary')
            ->size(Size::Small)
            ->button()
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
            ->outlined()
            ->size(Size::Small)
            ->record(fn (): Layout => $this->getLayoutRecord())
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
            ->record(fn (): Layout => $this->getLayoutRecord())
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
            ->fillForm(fn (self $livewire, $arguments): array => [
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

    public function editContainerWidgetAction(): Action
    {
        return Action::make('editContainerWidget')
            ->label(__('capell-admin::button.edit'))
            ->tooltip(__('capell-layout::button.edit_container_widget'))
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
                    SchemaTypeEnum::LayoutWidget->value,
                    $livewire->getContainerWidgetSchema($arguments['containerKey'], $arguments['widgetIndex']),
                );

                $typeSchema = resolve($adminSchema)->make($schema);

                return $schema->operation('editOption')->components($typeSchema);
            })
            ->fillForm(
                fn (self $livewire, array $arguments): array => $livewire->containers[$arguments['containerKey']]['widgets'][$arguments['widgetIndex']]['meta'] ?? [],
            )
            ->action(function (Action $action, self $livewire, array $arguments, array $data): void {
                $livewire->editContainerWidget($arguments['containerKey'], $arguments['widgetIndex'], $data);

                $action->success();
            });
    }

    public function addWidgetAction(): Action
    {
        return Action::make('addWidget')
            ->label(__('capell-layout::button.widget'))
            ->modalHeading(__('capell-layout::heading.add_widget_to_container'))
            ->icon('heroicon-c-plus')
            ->size(Size::Small)
            ->color('gray')
            ->outlined()
            ->visible(fn (): bool => (bool) $this->containers)
            ->modalWidth(Width::SixExtraLarge)
            ->extraModalWindowAttributes([
                'class' => 'capell-layout-builder-assets-table',
            ])
            ->modalContent(function (Action $action, array $arguments): HtmlString {
                /** @var self $livewire */
                $livewire = $action->getLivewire();

                return new HtmlString(Blade::render(
                    <<<'blade'
                       @livewire('capell.layout.livewire.layout.widget-table-select', [
                           'actionModalId' => $actionModalId,
                           'containerKey' => $containerKey,
                           'containers' => $containers,
                       ], key($livewireKey))
                   blade,
                    [
                        'actionModalId' => sprintf('fi-%s-action-%s', $livewire->getId(), $action->getNestingIndex()),
                        'containerKey' => $arguments['containerKey'] ?? '',
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
            ->modalWidth(Width::SixExtraLarge)
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
                        // Fetch fresh record to avoid using layout data and relationships
                        ->record(fn (): Widget => $action->getRecord()->fresh()),
                ),
            )
            ->action(
                function (Action $action, Schema $schema, Widget $record, array $data): void {
                    $schema->saveRelationships();

                    $record->update($data);

                    $action->success();
                },
            );
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
            ->extraModalWindowAttributes([
                'class' => 'capell-layout-builder-assets-table',
            ])
            ->icon('heroicon-c-magnifying-glass')
            ->iconSize(IconSize::Small)
            ->size(Size::Small)
            ->extraModalWindowAttributes([
                'class' => 'capell-layout-builder-assets-table',
            ])
            ->modalWidth(Width::SixExtraLarge)
            ->modalHeading(function (self $livewire, array $arguments): string {
                $totalAssets = $livewire->countWidgetAssets($arguments['containerKey'], $arguments['widgetIndex']);

                if ($totalAssets !== 0) {
                    $hasPageAssets = $livewire->hasPageAssets($arguments['containerKey'], $arguments['widgetIndex']);
                } else {
                    $hasPageAssets = (bool) $livewire->page_id;
                }

                return $hasPageAssets
                    ? __('capell-admin::generic.select_page_widget_asset_description', ['type' => $arguments['type']])
                    : __('capell-admin::generic.select_widget_asset_description', ['type' => $arguments['type']]);
            })
            ->modalContent(function (Action $action, array $arguments): HtmlString {
                /** @var self $livewire */
                $livewire = $action->getLivewire();

                $componentName = 'capell.layout.livewire.assets.table.' . $arguments['type'];

                $existingRecords = $livewire->getWidgetAssetsByType(
                    $arguments['containerKey'],
                    $arguments['widgetIndex'],
                    $arguments['type'],
                );

                return new HtmlString(Blade::render(
                    <<<'blade'
                       @livewire($componentName, [
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
                            'pageId' => $livewire->page_id,
                            'siteId' => $livewire->site_id,
                        ],
                        'componentName' => $componentName,
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
            ->modalWidth(Width::SixExtraLarge)
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
            ->modalWidth(Width::SixExtraLarge)
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
            ->fillForm(fn (self $livewire): array => ['layout_id' => $livewire->layout_id])
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
                if ($livewire->page_id === null || $livewire->page_id === 0) {
                    return false;
                }

                $widget = $livewire->getContainerWidget($arguments['containerKey'], $arguments['widgetIndex']);

                $assetTypes = empty($widget->admin['asset_types'])
                    ? $widget->type->admin['asset_types'] ?? []
                    : ($widget->admin['asset_types']);

                if (! $assetTypes) {
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
                $hasPageAssets = $livewire->hasPageAssets(
                    containerKey: $arguments['containerKey'],
                    widgetIndex: $arguments['widgetIndex'],
                );

                $livewire->togglePageAssets(
                    $arguments['containerKey'],
                    $arguments['widgetIndex'],
                    pageId: $hasPageAssets ? null : $livewire->page_id,
                );

                $action->success();
            });
    }

    public function selectAllAssets(string $containerKey, int $widgetIndex): void
    {
        $this->selectedRecords[$containerKey][$widgetIndex] = $this->getAllSelectableAssetsKeys(
            $containerKey,
            $widgetIndex,
        );
    }

    public function deSelectAllAssets(string $containerKey, int $widgetIndex): void
    {
        $this->selectedRecords[$containerKey][$widgetIndex] = [];
    }

    public function getLayoutRecord(): Layout
    {
        if (! isset($this->layoutRecord)) {
            $this->loadLayoutRecord();
        }

        return $this->layoutRecord;
    }

    public function getLayoutPagesCount(): int
    {
        if (! property_exists($this->layoutRecord, 'pages_count')) {
            $this->layoutRecord->loadCount('pages');
        }

        return $this->layoutRecord->pages_count;
    }

    public function hasPageAssets(string $containerKey, int $widgetIndex): bool
    {
        if (! $this->inPageContext()) {
            return false;
        }

        $assets = $this->getWidgetAssets($containerKey, $widgetIndex);

        if ($assets === []) {
            return false;
        }

        return collect($assets)->contains('page_id', $this->page_id);
    }

    public function widgetHasPageAssets(Widget $widget): bool
    {
        if (! $this->inPageContext()) {
            return $widget->assets()->whereNotNull('page_id')->exists();
        }

        if (property_exists($widget, 'page_assets_count')) {
            return $widget->page_assets_count > 0;
        }

        return $widget->assets()->where('page_id', $this->page_id)->exists();
    }

    public function widgetHasGlobalAssets(Widget $widget): bool
    {
        if (property_exists($widget, 'global_assets_count')) {
            return $widget->global_assets_count > 0;
        }

        return $widget->assets()->whereNull('page_id')->exists();
    }

    #[Computed]
    public function page(): ?Page
    {
        if ($this->page_id !== null && $this->page_id !== 0) {
            return Page::query()->find($this->page_id);
        }

        return null;
    }

    /**
     * @return class-string<resource>
     */
    public function getPageResource(): string
    {
        return $this->page
            ? GetResourceFromTypeAction::run(ResourceEnum::Page, $this->page->type)
            : CapellAdmin::getResource(ResourceEnum::Page);
    }

    /**
     * @return class-string<resource>
     */
    public function getCurrentResource(): string
    {
        if ($this->page_id !== null && $this->page_id !== 0) {
            return $this->getPageResource();
        }

        return CapellAdmin::getResource(ResourceEnum::Layout);
    }

    public function placeholder(array $params = []): View
    {
        return view('capell-admin::components.placeholder', $params);
    }

    public function render(): View
    {
        if (! isset($this->layoutRecord)) {
            $this->loadFromStore();
        }

        return view($this->view);
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
            $asset['page_id'] = $this->page_id;
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

    protected function moveContainerWidget(string $originalContainer, int $originalIndex, string $containerKey, int $widgetIndex): void
    {
        $widget = $this->getContainerWidget($originalContainer, $originalIndex);

        $containerWidget = $this->containers[$originalContainer]['widgets'][$originalIndex];

        if ($originalContainer !== $containerKey) {
            $containerWidget['occurrence'] = $this->getLastContainerWidgetOccurrence(
                containerKey: $containerKey,
                widgetKey: $containerWidget['widget_key'],
                widgets: $this->containers[$containerKey]['widgets'],
            ) + 1;
        }

        $widgets = $this->containers[$originalContainer]['widgets'];

        unset($widgets[$originalIndex]);

        $this->containers[$originalContainer]['widgets'] = array_values($widgets);

        $widgets = $this->containers[$containerKey]['widgets'];
        $widgets = array_merge(array_slice($widgets, 0, $widgetIndex), [$containerWidget], array_slice($widgets, $widgetIndex));
        $this->containers[$containerKey]['widgets'] = $widgets;

        if ($containerKey !== $originalContainer) {
            unset($this->containerWidgets[$originalContainer][$originalIndex]);
            $this->containerWidgets[$originalContainer] = array_values($this->containerWidgets[$originalContainer]);
        }

        $containerWidgets = $this->containerWidgets[$containerKey] ?? [];
        $containerWidgets = array_merge(array_slice($containerWidgets, 0, $widgetIndex), [$widget], array_slice($containerWidgets, $widgetIndex));
        $this->containerWidgets[$containerKey] = $containerWidgets;

        $this->updatePageAssets($containerKey, $widgetIndex);
    }

    protected function updatePageAssets(string $containerKey, int $widgetIndex, ?bool $hasPageAssets = null): void
    {
        if (! $this->assets[$containerKey][$widgetIndex]) {
            return;
        }

        if ($hasPageAssets === null) {
            $hasPageAssets = $this->hasPageAssets($containerKey, $widgetIndex);
        }

        foreach ($this->assets[$containerKey][$widgetIndex] as $assetIndex => $asset) {
            $this->assets[$containerKey][$widgetIndex][$assetIndex]['page_id'] = $hasPageAssets ? $this->page_id : null;
        }
    }

    protected function moveContainerWidgetAssets(string $originalContainer, int $originalIndex, string $containerKey, int $widgetIndex): void
    {
        $widget = $this->assets[$originalContainer][$originalIndex];

        $assets = $this->assets[$containerKey] ?? [];
        $assets = array_merge(array_slice($assets, 0, $widgetIndex), [$widget], array_slice($assets, $widgetIndex));
        $this->assets[$containerKey] = $assets;

        if ($containerKey !== $originalContainer) {
            unset($this->assets[$originalContainer][$originalIndex]);
            $this->assets[$originalContainer] = array_values($this->assets[$originalContainer]);
        }

        $selectedRecords = $this->selectedRecords[$containerKey];
        $selectedRecords = array_merge(array_slice($selectedRecords, 0, $widgetIndex), [[]], array_slice($selectedRecords, $widgetIndex));
        $this->selectedRecords[$containerKey] = $selectedRecords;

        unset($this->selectedRecords[$originalContainer][$originalIndex]);
        $this->selectedRecords[$originalContainer] = array_values($this->selectedRecords[$originalContainer]);
    }

    private function duplicateLayout(): void
    {
        $newLayout = ReplicateLayoutAction::run($this->getLayoutRecord());

        $this->layout_id = $newLayout->id;

        $this->reload();

        $this->dispatch('page-layout-changed', id: $this->layout_id);
    }

    /**
     * Build a new WidgetAsset record for the addAsset action form.
     */
    private function makeWidgetAssetRecordForCreate(array $arguments): WidgetAsset
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

    /**
     * Resolve the existing WidgetAsset for editing based on action arguments.
     */
    private function resolveEditableWidgetAsset(array $arguments): WidgetAsset
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

    /**
     * Build the modal heading for the editWidgetAsset action.
     */
    private function getEditWidgetAssetModalHeading(self $livewire, array $arguments): string
    {
        $name = str($arguments['type'])->title();

        if ($livewire->inPageContext()) {
            return __('capell-layout::heading.edit_page_widget_asset', ['name' => $name]);
        }

        return __('capell-layout::heading.edit_widget_asset', ['name' => $name]);
    }

    /**
     * Build the optional modal description for the editWidgetAsset action.
     */
    private function getEditWidgetAssetModalDescription(self $livewire, array $arguments): ?string
    {
        if (! $livewire->inPageContext()) {
            return null;
        }

        $widgetAsset = $this->getWidgetAsset($arguments['containerKey'], $arguments['widgetIndex'], $arguments['index']);

        if (empty($widgetAsset['page_id'])) {
            return null;
        }

        return __('capell-layout::heading.page_widget_asset', ['name' => $livewire->getLayoutPage()->name]);
    }

    /**
     * Apply updates for the editWidgetAsset action and trigger UI refresh/notifications.
     */
    private function applyWidgetAssetUpdate(WidgetAsset $record, array $data, self $livewire, array $arguments, Action $action, Schema $schema): void
    {
        $schema->saveRelationships();

        if ($data !== []) {
            $record->update($data);
        }

        if (! empty($data['meta'])) {
            $livewire->updateWidgetAsset($arguments['containerKey'], $arguments['widgetIndex'], $arguments['index'], ['meta' => $data['meta']]);
        }

        $livewire->reloadContainerWidgetAsset($arguments['containerKey'], $arguments['widgetIndex'], $arguments['index']);

        $livewire->notifyPageCached($record);

        $action->success();
    }

    private function getChangeLayoutSchema(): array
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
                            $this->site_id,
                            fn (Builder $query, int $siteId): Builder => $query->where(
                                fn (Builder $query) => $query->where('site_id', $siteId)
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
                ->default(fn () => CapellCore::getModel(CoreModelEnum::Layout)::default()->first(['id'])?->id)
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
                                        ['tableFilters' => ['layout_id' => ['value' => $state]]],
                                    ),
                                ],
                            ),
                        );
                    },
                ),
        ];
    }

    private function changePageLayout(int $layoutId): void
    {
        if (! $this->getLayoutPage() instanceof Page) {
            return;
        }

        $this->layout_id = $layoutId;

        $this->reload();

        $this->layoutUpdated();
    }

    private function loadNew(): void
    {
        $this->loadLayoutRecord();

        $this->setupContainers();

        $widgets = $this->preloadAllWidgets();

        foreach (array_keys($this->containers) as $containerKey) {
            $this->setupContainerWidgets($containerKey, $widgets);
        }

        $this->setupSelectedAssets();
    }

    private function loadFromStore(): void
    {
        $this->loadLayoutRecord();

        $widgets = $this->preloadAllWidgets(withAssets: false);

        $allWidgetAssets = $this->preloadAllWidgetAssets();

        $containerWidgetAssets = [];

        foreach ($this->assets as $containerKey => $containerWidgets) {
            foreach ($containerWidgets as $widgetIndex => $widgetAssets) {
                $containerWidgetAssets[$containerKey][$widgetIndex] = $this->setupWidgetAssets(
                    $containerKey,
                    $widgetIndex,
                    $widgetAssets,
                    $allWidgetAssets,
                );
            }
        }

        foreach (array_keys($this->containers) as $containerKey) {
            $this->setupContainerWidgets($containerKey, $widgets, $containerWidgetAssets);
        }
    }

    private function reload(): void
    {
        $this->reset('containerWidgets', 'selectedRecords', 'assets', 'containers', 'layoutRecord');

        $this->loadNew();
    }

    private function getSelectedAssets(string $containerKey, int $widgetIndex): array
    {
        return $this->selectedRecords[$containerKey][$widgetIndex] ?? [];
    }

    private function getAllSelectableAssetsKeys(string $containerKey, int $widgetIndex): array
    {
        return collect($this->assets[$containerKey][$widgetIndex])
            ->map(fn (array $widgetAsset): string => sprintf('%s.%s', $widgetAsset['asset_type'], $widgetAsset['asset_id']))
            ->values()
            ->all();
    }

    private function loadLayoutRecord(): Layout
    {
        if (isset($this->layoutRecord)) {
            return $this->layoutRecord;
        }

        /** @var class-string<Layout> $model */
        $model = CapellCore::getModel(CoreModelEnum::Layout);

        $layout = $model::query()->withCount('pages')->find($this->layout_id);

        throw_unless($layout, Exception::class, 'Layout not found');

        $this->layoutRecord = $layout;

        return $this->layoutRecord;
    }

    private function saveContainer(array $data, ?string $key = null): void
    {
        $this->loadFromStore();

        if (in_array($key, [null, '', '0'], true)) {
            $key = $data['key'];
        }

        if ($key !== $data['key']) {
            $key = $this->updateContainerKey($key, $data['key']);
        }

        if (! isset($this->containers[$key])) {
            $this->addContainer($key);
        }

        $this->containers[$key]['meta'] = $data['meta'] ?? [];

        $this->setupSelectedAssets();

        $this->layoutUpdated();
    }

    private function removeContainer(string $containerKey): void
    {
        foreach (['containers', 'containerWidgets', 'assets'] as $property) {
            if (! isset($this->{$property}[$containerKey])) {
                continue;
            }

            unset($this->{$property}[$containerKey]);
        }

        $this->layoutUpdated();
    }

    private function addWidgetToContainer(Widget $widget, string $containerKey): int
    {
        $occurrence = $this->getLastContainerWidgetOccurrence($containerKey, $widget->key) + 1;

        $this->containers[$containerKey]['widgets'][] = [
            'widget_key' => $widget->key,
            'occurrence' => $occurrence,
        ];

        $index = array_key_last($this->containers[$containerKey]['widgets']);

        $this->containerWidgets[$containerKey][$index] = $widget;

        $this->assets[$containerKey][$index] = [];

        return $index;
    }

    private function duplicateWidget(string $containerKey, int $originalIndex, bool $withAssets = true): void
    {
        $containerWidget = $this->containers[$containerKey]['widgets'][$originalIndex];

        $containerWidget['occurrence'] = $this->getLastContainerWidgetOccurrence($containerKey, $containerWidget['widget_key']) + 1;

        $this->containers[$containerKey]['widgets'][] = $containerWidget;

        $this->containerWidgets[$containerKey][] = $this->getContainerWidget($containerKey, $originalIndex);
        $widgetIndex = array_key_last($this->containerWidgets[$containerKey]);

        if ($withAssets) {
            $this->assets[$containerKey][$widgetIndex] = $this->assets[$containerKey][$originalIndex];
        }

        $this->layoutUpdated();
    }

    private function removeWidget(string $containerKey, int $widgetIndex): void
    {
        if (isset($this->containers[$containerKey]['widgets'][$widgetIndex])) {
            unset($this->containers[$containerKey]['widgets'][$widgetIndex]);
        }

        if (isset($this->containerWidgets[$containerKey][$widgetIndex])) {
            unset($this->containerWidgets[$containerKey][$widgetIndex]);
        }

        if (isset($this->assets[$containerKey][$widgetIndex])) {
            unset($this->assets[$containerKey][$widgetIndex]);
        }

        if (isset($this->selectedRecords[$containerKey][$widgetIndex])) {
            unset($this->selectedRecords[$containerKey][$widgetIndex]);
        }

        $this->layoutUpdated();
    }

    private function removeSelectedAssets(string $containerKey, int $widgetIndex): void
    {
        foreach ($this->selectedRecords[$containerKey][$widgetIndex] as $asset) {
            [$type, $uuid] = explode('.', (string) $asset);

            if (is_numeric($uuid)) {
                $uuid = (int) $uuid;
            }

            $this->removeAsset($containerKey, $widgetIndex, $uuid, $type);
        }

        $this->assets[$containerKey][$widgetIndex] = array_values($this->assets[$containerKey][$widgetIndex]);

        $this->selectedRecords[$containerKey][$widgetIndex] = [];

        $this->layoutUpdated();
    }

    private function removeAsset(string $containerKey, int $widgetIndex, mixed $uuid, string $type): void
    {
        foreach ($this->assets[$containerKey][$widgetIndex] as $index => $widgetAsset) {
            if ($widgetAsset['asset_id'] !== $uuid) {
                continue;
            }

            if ($widgetAsset['asset_type'] !== $type) {
                continue;
            }

            unset($this->assets[$containerKey][$widgetIndex][$index]);
        }
    }

    private function editContainerWidget(string $containerKey, int $widgetIndex, array $data): void
    {
        $this->containers[$containerKey]['widgets'][$widgetIndex]['meta'] = array_merge(
            $this->containers[$containerKey]['widgets'][$widgetIndex]['meta'] ?? [],
            $data,
        );

        $this->layoutUpdated();
    }

    private function getContainerWidgetOccurrence(string $containerKey, int $widgetIndex): int
    {
        return (int) ($this->containers[$containerKey]['widgets'][$widgetIndex]['occurrence'] ?? 1);
    }

    private function getLastContainerWidgetOccurrence(string $containerKey, string $widgetKey, ?int $compareIndex = null, ?array $widgets = null): int
    {
        if ($widgets === null || $widgets === []) {
            $widgets = $this->containers[$containerKey]['widgets'];
        }

        $occurrence = 1;

        foreach ($widgets as $widgetIndex => $widget) {
            if ($compareIndex !== null && $widgetIndex === $compareIndex) {
                return $occurrence;
            }

            if ($widget['widget_key'] === $widgetKey) {
                $occurrence++;
            }
        }

        return $occurrence;
    }

    private function getLayoutPage(): ?Page
    {
        if (! $this->inPageContext()) {
            return null;
        }

        if ($this->layoutPage instanceof Page) {
            return $this->layoutPage;
        }

        /** @var class-string<Page> $model */
        $model = CapellCore::getModel(CoreModelEnum::Page);

        $this->layoutPage = $model::withTrashed()->withDrafts()->find($this->page_id);

        return $this->layoutPage;
    }

    private function getSite(): ?Site
    {
        if ($this->site instanceof Site) {
            return $this->site;
        }

        if ($this->getLayoutPage() instanceof Page) {
            return $this->getLayoutPage()->site;
        }

        if ($this->site_id === null || $this->site_id === 0) {
            return null;
        }

        /** @var class-string<Site> $model */
        $model = CapellCore::getModel(CoreModelEnum::Site);

        return $model::query()->find($this->site_id);
    }

    private function setupContainers(): void
    {
        if ($this->containers !== null) {
            return;
        }

        $this->containers = [];

        if (! $this->layoutRecord->containers) {
            return;
        }

        foreach ($this->layoutRecord->containers as $key => $container) {
            $this->containers[$key] = [
                'widgets' => $container['widgets'] ?? [],
                'meta' => $container['meta'] ?? [],
            ];
        }
    }

    private function setupSelectedAssets(): void
    {
        $this->selectedRecords = [];

        foreach ($this->containers as $containerKey => $container) {
            $this->selectedRecords[$containerKey] = [];

            foreach ($container['widgets'] as $widgetIndex => $widget) {
                $this->selectedRecords[$containerKey][$widgetIndex] = [];
            }
        }
    }

    private function setupContainerWidgets(string $containerKey, array $allWidgets, ?array $allWidgetAssets = null): void
    {
        $container = $this->containers[$containerKey];

        $widgetOccurrences = [];

        foreach ($container['widgets'] as $widgetIndex => $containerWidget) {
            $widgetKey = $containerWidget['widget_key'];
            $oldContainerKey = $containerWidget['old_container'] ?? null;

            throw_unless(isset($allWidgets[$widgetKey]), Exception::class, 'Widget not found for key: ' . $widgetKey);

            /** @var Widget $widget */
            $widget = clone $allWidgets[$widgetKey];

            if (! isset($widgetOccurrences[$widgetKey])) {
                $widgetOccurrences[$widgetKey] = 1;
            } else {
                $widgetOccurrences[$widgetKey]++;
            }

            $widgetOccurrence = $widgetOccurrences[$widgetKey];

            $this->containers[$containerKey]['widgets'][$widgetIndex]['occurrence'] = $widgetOccurrence;

            if ($allWidgetAssets !== null) {
                $assets = $allWidgetAssets[$containerKey][$widgetIndex] ?? new Collection;
            } elseif ($widget->relationLoaded('assets')) {
                $assets = $widget->assets;
            } else {
                $assets = $this->loadWidgetAssets($widget, $containerKey, $widgetOccurrence);
            }

            $widget->setRelation(
                'assets',
                $this->filterContainerWidgetAssets($assets, $oldContainerKey ?? $containerKey, $widgetOccurrence),
            );

            $this->containerWidgets[$containerKey][$widgetIndex] = $widget;

            $this->assets[$containerKey][$widgetIndex] = $this->mapWidgetAssets($widget, $containerKey, $oldContainerKey);

            $this->updatePageAssets($containerKey, $widgetIndex);
        }
    }

    /**
     * Normalize widget assets collection into a plain array structure for internal storage.
     */
    private function mapWidgetAssets(Widget $widget, string $containerKey, ?string $oldContainerKey = null): array
    {
        return $widget->assets->map(
            static function (WidgetAsset $widgetAsset) use ($containerKey, $oldContainerKey): array {
                $asset = [
                    'id' => $widgetAsset->id,
                    'asset_id' => is_numeric($widgetAsset->asset_id) ? (int) $widgetAsset->asset_id : $widgetAsset->asset_id,
                    'asset_type' => $widgetAsset->asset_type,
                    'meta' => $widgetAsset->meta,
                    'order' => $widgetAsset->order,
                    'occurrence' => $widgetAsset->occurrence,
                ];

                if ($widgetAsset->page_id) {
                    $asset['page_id'] = $widgetAsset->page_id;
                    $asset['container'] = $containerKey;
                }

                if ($oldContainerKey) {
                    $asset['old_container'] = $oldContainerKey;
                }

                return $asset;
            },
        )->all();
    }

    private function setupWidgetAssets(string $containerKey, int $widgetIndex, array $widgetAssets, ?Collection $allWidgetAssets): Collection
    {
        $assets = new Collection;

        if (! $allWidgetAssets instanceof Collection || $allWidgetAssets->isEmpty()) {
            return $assets;
        }

        $occurrence = $this->getContainerWidgetOccurrence($containerKey, $widgetIndex);

        foreach ($widgetAssets as $widgetAssetData) {
            $type = $widgetAssetData['asset_type'];
            $assetId = is_numeric($widgetAssetData['asset_id']) ? (int) $widgetAssetData['asset_id'] : $widgetAssetData['asset_id'];

            $oldContainerKey = $widgetAssetData['old_container'] ?? $containerKey;

            /** @var ?WidgetAsset $matchingAsset */
            $matchingAsset = $allWidgetAssets->first(function (WidgetAsset $asset) use ($type, $assetId, $oldContainerKey, $occurrence): bool {
                $matchesWidget = $asset->asset_type === $type
                    && (int) $asset->asset_id === $assetId;

                if (! $matchesWidget) {
                    return false;
                }

                $matchesOccurrence = (int) $asset->occurrence === $occurrence;

                if (! $matchesOccurrence) {
                    return false;
                }

                if (! $this->inPageContext()) {
                    return $asset->page_id === null;
                }

                $matchesPage = $asset->page_id === null || $asset->page_id === $this->page_id;
                $matchesContainer = $asset->container === null || $asset->container === $oldContainerKey;

                return $matchesPage && $matchesContainer;
            });

            if ($matchingAsset === null) {
                continue;
            }

            $widgetAsset = clone $matchingAsset;
            $widgetAsset->order = $widgetAssetData['order'] ?? $widgetAsset->order;
            $widgetAsset->page_id = $widgetAssetData['page_id'] ?? null;

            $assets->push($widgetAsset);
        }

        return $assets;
    }

    private function getContainerSchema(Schema $schema, array $arguments): array
    {
        $containerKey = $arguments['containerKey'] ?? null;

        $adminSchema = CapellAdmin::getSchema(
            SchemaTypeEnum::LayoutContainer->value,
            $this->layoutRecord->admin['container_schema'][$containerKey] ?? DefaultLayoutContainerSchema::getKey(),
        );

        $typeSchema = resolve($adminSchema)->make($schema);

        return [
            TextInput::make('key')
                ->label(__('capell-admin::form.key'))
                ->placeholder(__('capell-admin::generic.key_placeholder'))
                ->helperText(__('Lowercase text, numbers, hyphens, and underscores only'))
                ->alphaDash()
                ->required()
                ->maxLength(128)
                ->afterStateHydrated(
                    fn (TextInput $component, $state): TextInput => $component->state(
                        str($state)->slug()->lower()->toString(),
                    ),
                )
                ->dehydrateStateUsing(fn ($state): string => str($state)->slug()->lower()->toString())
                ->rules([
                    fn (LayoutBuilder $livewire): Closure => function (string $attribute, $value, Closure $fail) use ($livewire, $containerKey): void {
                        if (! isset($livewire->containers[$value]) || ($containerKey && $containerKey === $value)) {
                            return;
                        }

                        $fail(__('capell-layout::message.layout_container_key_not_unique', ['key' => $value]));
                    },
                ]),
            ...$typeSchema,
        ];
    }

    private function getWidgetAssetSchema(Schema $schema): Schema
    {
        return WidgetAssetForm::configure($schema);
    }

    private function updateContainerKey(string $oldKey, string $newKey): string
    {
        foreach (['containers', 'containerWidgets', 'assets'] as $property) {
            if (! isset($this->{$property}[$oldKey])) {
                continue;
            }

            $this->{$property}[$newKey] = $this->{$property}[$oldKey];

            unset($this->{$property}[$oldKey]);
        }

        foreach ($this->containers[$newKey]['widgets'] as $widgetIndex => $widget) {
            $widget['old_container'] ??= $oldKey;
            $widget['container_key'] = $newKey;

            $this->containers[$newKey]['widgets'][$widgetIndex] = $widget;
        }

        foreach ($this->assets[$newKey] ?? [] as $widgetIndex => $widgetAssets) {
            foreach ($widgetAssets as $assetIndex => $asset) {
                $asset['old_container'] ??= $oldKey;
                $asset['container'] = $newKey;

                $this->assets[$newKey][$widgetIndex][$assetIndex] = $asset;
            }
        }

        if (isset($this->selectedRecords[$oldKey])) {
            $this->selectedRecords[$newKey] = $this->selectedRecords[$oldKey];

            unset($this->selectedRecords[$oldKey]);
        }

        return $newKey;
    }

    private function addContainer(string $key): void
    {
        $this->containers[$key] = [
            'widgets' => [],
        ];

        $this->containerWidgets[$key] = [];

        $this->assets[$key] = [];
    }

    private function layoutUpdated(bool $modified = true): void
    {
        $this->layoutModified = $modified;
    }

    /**
     * @return Builder<Widget>
     */
    private function getWidgetQuery(bool $withRelations = true): Builder
    {
        /** @var class-string<Widget> $model */
        $model = CapellCore::getModel(ModelEnum::Widget->name);

        return $model::query()
            ->when(
                $withRelations,
                fn (Builder $query) => $query->withCount([
                    'layouts',
                    'widgetPageAssets as page_assets_count' => fn (Builder $query): Builder => $query->distinct('page_id')
                        ->when(
                            $this->inPageContext(),
                            fn (Builder $query) => $query->where('page_id', $this->page_id),
                        ),
                ])
                    ->with([
                        'type',
                        'backgroundImage',
                        'image',
                        'translation' => fn (BuilderContract $query) => $query->orderBy('language_id'),
                    ]),
            );
    }

    private function loadWidget(string $containerKey, int $widgetIndex, bool $withAssets = true): Widget
    {
        $container = $this->containers[$containerKey] ?? null;

        throw_if($container === null || ! isset($container['widgets'][$widgetIndex]), Exception::class, 'Container widget not found for container: ' . $containerKey . ' index: ' . $widgetIndex);

        $containerWidget = $container['widgets'][$widgetIndex];
        $widgetKey = $containerWidget['widget_key'];
        $occurrence = $containerWidget['occurrence'] ?? 1;

        $widget = $this->getWidget($widgetKey);

        if ($withAssets) {
            $widget->setRelation('assets', $this->loadWidgetAssets($widget, $containerKey, $occurrence));
        }

        $this->containerWidgets[$containerKey][$widgetIndex] = $widget;

        return $widget;
    }

    private function loadWidgetAssets(Widget $widget, string $containerKey, int $widgetOccurrence): Collection
    {
        /** @var class-string<WidgetAsset> $model */
        $model = CapellCore::getModel(ModelEnum::WidgetAsset->name);

        $assets = $model::query()
            ->with([
                'asset' => fn (BuilderContract $query): BuilderContract => $query->morphWith($this->getAssetRelations()),
            ])
            ->where('widget_id', $widget->id)
            ->where('occurrence', $widgetOccurrence)
            ->where(
                fn (Builder $query) => $query->where('container', $containerKey)
                    ->orWhereNull('container'),
            )
            ->when(
                $this->page_id,
                fn (Builder $query) => $query->where(
                    fn (Builder $query) => $query->where('page_id', $this->page_id)
                        ->orWhereNull('page_id'),
                ),
                fn (Builder $query) => $query->whereNull('page_id'),
            )
            ->ordered()
            ->get()
            ->each->setRelation('widget', $widget);

        return $this->filterContainerWidgetAssets($assets, $containerKey, $widgetOccurrence);
    }

    private function preloadAllWidgets(bool $withAssets = true): ?array
    {
        $widgetKeys = $this->getContainerWidgetKeys();

        if ($widgetKeys === []) {
            return null;
        }

        $allWidgetAssets = $this->getWidgetQuery()
            ->whereIn('key', $widgetKeys)
            ->when(
                $withAssets,
                fn (Builder $query) => $query->with([
                    'assets' => fn (BuilderContract $query): BuilderContract => $query->when(
                        $this->page_id,
                        fn (Builder $query) => $query->where(
                            fn (Builder $query) => $query->where('page_id', $this->page_id)
                                ->orWhereNull('page_id'),
                        ),
                        fn (Builder $query) => $query->whereNull('page_id'),
                    )
                        ->ordered()
                        ->with(
                            'asset',
                            fn (BuilderContract $query) => $query->morphWith($this->getAssetRelations()),
                        ),
                ]),
            )
            ->get()
            ->keyBy('key')
            ->all();

        if ($withAssets) {
            foreach ($allWidgetAssets as $widgetAssets) {
                $hasPageAssets = $widgetAssets->assets->whereNotNull('page_id')->isNotEmpty();

                if ($hasPageAssets) {
                    $widgetAssets->setRelation('assets', $widgetAssets->assets->filter(
                        fn (WidgetAsset $asset): bool => $asset->page_id !== null,
                    ));
                }
            }
        }

        return $allWidgetAssets;
    }

    /**
     * Preload all widget assets currently tracked in $this->assets.
     *
     * @return Collection<WidgetAsset>|null
     */
    private function preloadAllWidgetAssets(): ?Collection
    {
        $widgetAssets = collect($this->assets)->flatten(2);

        if ($widgetAssets->isEmpty()) {
            return null;
        }

        $existingIds = $widgetAssets
            ->filter(fn ($asset): bool => isset($asset['id']))
            ->pluck('id')
            ->all();

        $newAssets = $widgetAssets
            ->reject(fn ($asset): bool => isset($asset['id']))
            ->all();

        return $this->buildPreloadedWidgetAssets($existingIds, $newAssets);
    }

    /**
     * Preload widget assets for a specific container/widget index.
     *
     * @return Collection<WidgetAsset>|null
     */
    private function loadWidgetAssetsFor(Widget $widget, string $containerKey, int $widgetIndex): Collection
    {
        $occurrence = $this->getContainerWidgetOccurrence($containerKey, $widgetIndex);

        $widgetAssets = collect($this->assets[$containerKey][$widgetIndex] ?? []);

        if ($widgetAssets->isEmpty()) {
            return new Collection;
        }

        $existingIds = $widgetAssets
            ->filter(fn ($asset): bool => isset($asset['id']))
            ->pluck('id')
            ->all();

        $newAssets = $widgetAssets
            ->reject(fn ($asset): bool => isset($asset['id']))
            ->all();

        $assets = $this->buildPreloadedWidgetAssets($existingIds, $newAssets);

        return $this->filterContainerWidgetAssets($assets, $containerKey, $occurrence)
            ->each->setRelation('widget', $widget);
    }

    /**
     * Build the collection of preloaded WidgetAsset models (shared implementation).
     *
     * @param  array<int,mixed>  $existingIds
     * @param  array<int,array>  $newAssets
     * @return Collection<WidgetAsset>
     */
    private function buildPreloadedWidgetAssets(array $existingIds, array $newAssets): Collection
    {
        /** @var class-string<WidgetAsset> $model */
        $model = CapellCore::getModel(ModelEnum::WidgetAsset->name);

        $existingAssets = $existingIds === []
            ? (new $model)->newCollection()
            : $model::query()
                ->whereKey($existingIds)
                ->when(
                    $this->page_id,
                    fn (Builder $query) => $query->where(
                        fn (Builder $query) => $query->where('page_id', $this->page_id)->orWhereNull('page_id'),
                    ),
                    fn (Builder $query) => $query->whereNull('page_id'),
                )
                ->when(
                    DB::getDriverName() === 'sqlite',
                    fn (BuilderContract $query) => $query->orderByRaw(
                        'CASE id '
                        . implode(' ', array_map(
                            fn ($id, $pos): string => sprintf('WHEN %d THEN %d', (int) $id, $pos),
                            $existingIds,
                            array_keys($existingIds),
                        ))
                          . ' END',
                    ),
                    fn (BuilderContract $query) => $query->orderByRaw('FIELD(id, ' . implode(',', array_map(intval(...), $existingIds)) . ')'),
                )
                ->get();

        $newAssetsCollection = collect($newAssets)
            ->values()
            ->map(fn (array $data) => $model::query()->newModelInstance()->forceFill($data));

        $allAssets = (new $model)->newCollection(array_merge($existingAssets->all(), $newAssetsCollection->all()));

        return $allAssets
            ->load(['asset' => fn (BuilderContract $query) => $query->morphWith($this->getAssetRelations())])
            ->map(function (WidgetAsset $widgetAsset): WidgetAsset {
                if (is_numeric($widgetAsset->asset_id)) {
                    $widgetAsset->asset_id = (int) $widgetAsset->asset_id;
                }

                return $widgetAsset;
            });
    }

    private function filterContainerWidgetAssets(Collection $assets, string $containerKey, int $widgetOccurrence): Collection
    {
        return $assets->filter(function (WidgetAsset $widgetAsset) use ($containerKey, $widgetOccurrence): bool {
            if ($widgetAsset->container === null) {
                return true;
            }

            if ($widgetAsset->container !== $containerKey) {
                return false;
            }

            if ((int) $widgetAsset->occurrence !== $widgetOccurrence) {
                return false;
            }

            if ($this->page_id !== null && $this->page_id !== 0) {
                return $widgetAsset->page_id === null || (int) $widgetAsset->page_id === $this->page_id;
            }

            return $widgetAsset->page_id === null;
        });
    }

    private function getContainerOptions(): SupportCollection
    {
        return collect($this->containers)
            ->keys()
            ->mapWithKeys(fn (string $container): array => [$container => __($container)]);
    }

    private function getAssetRelations(): array
    {
        $relations = [];
        foreach (CapellCore::getAssets() as $asset) {
            $model = $asset->model;
            $relations[$model] = method_exists($model, 'getMorphRelations') ? $model::getMorphRelations() : [];

            if (! in_array('site', $relations[$model], true) && method_exists($model, 'site')) {
                $relations[$model][] = 'site';
            }
        }

        return $relations;
    }

    private function reloadContainerWidgetAsset(string $containerKey, int $widgetIndex, int $index): void
    {
        $widget = $this->getContainerWidget($containerKey, $widgetIndex);

        $widget->assets[$index]->fresh();
    }

    /**
     * @throws Exception
     */
    private function getWidget(int|string $id, bool $withRelations = true): Widget
    {
        $query = $this->getWidgetQuery(withRelations: $withRelations);

        if (is_numeric($id)) {
            $query->whereKey($id);
        } else {
            $query->where('key', $id);
        }

        /** @var Widget|null $widget */
        $widget = $query->first();

        throw_unless($widget, Exception::class, sprintf("Unable to find '%s' widget", (string) $id));

        return $widget;
    }

    private function getContainerWidgetKeys(): array
    {
        return collect($this->containers)
            ->pluck('widgets.*.widget_key')
            ->flatten()
            ->unique()
            ->toArray();
    }

    private function addAssets(string $containerKey, int $widgetIndex, ?bool $hasPageAssets, string $type, mixed $assets, array $assetsMeta = []): void
    {
        if (! isset($this->assets[$containerKey][$widgetIndex])) {
            $this->assets[$containerKey][$widgetIndex] = [];
        }

        $widget = $this->getContainerWidget($containerKey, $widgetIndex);

        $occurrence = $this->getContainerWidgetOccurrence($containerKey, $widgetIndex);

        $order = $this->countWidgetAssets($containerKey, $widgetIndex);

        foreach (Arr::wrap($assets) as $assetId) {
            $order++;

            $meta = $assetsMeta[$assetId] ?? [];

            $asset = [
                'asset_id' => $assetId,
                'asset_type' => $type,
                'meta' => $meta,
                'widget_id' => $widget->id,
                'order' => $order,
                'occurrence' => $occurrence,
            ];

            if ($hasPageAssets === true) {
                $asset['page_id'] = $this->page_id;
                $asset['container'] = $containerKey;
            }

            $this->assets[$containerKey][$widgetIndex][] = $asset;

            $widgetAsset = $this->addWidgetAsset(
                widget: $widget,
                containerKey: $containerKey,
                type: $type,
                hasPageAssets: $hasPageAssets,
                assetId: $assetId,
                meta: $meta,
                occurrence: $occurrence,
                order: $order,
            );

            $widgetAsset->setRelation('widget', $widget);

            $widget->assets->add($widgetAsset);
        }

        $widget->assets->load([
            'asset' => fn (BuilderContract $query): BuilderContract => $query->morphWith($this->getAssetRelations()),
        ]);

        $this->containerWidgets[$containerKey][$widgetIndex] = $widget;
    }

    private function updateAssets(string $containerKey, int $widgetIndex, ?string $oldContainerKey = null): void
    {
        $oldContainerKey ??= $containerKey;

        $assets = $this->assets[$containerKey][$widgetIndex] ?? [];

        $widget = $this->getContainerWidget($containerKey, $widgetIndex);

        $occurrence = $this->getContainerWidgetOccurrence($containerKey, $widgetIndex);

        $widgetHasPageAssets = $assets ? $this->widgetHasPageAssets($widget) : $this->inPageContext();

        $hasPageAssets = $assets ? $this->hasPageAssets($containerKey, $widgetIndex) : $this->inPageContext();

        $existingAssets = $widget->assets()
            ->where('occurrence', $occurrence)
            ->when(
                $widgetHasPageAssets,
                fn (Builder $query) => $query
                    ->where('container', $oldContainerKey)
                    ->where('page_id', $this->page_id),
                fn (Builder $query) => $query->whereNull('page_id'),
            )
            ->get()
            ->mapWithKeys(fn (WidgetAsset $widgetAsset): array => [$widgetAsset->asset_key => $widgetAsset]);

        if ($existingAssets->isNotEmpty()) {
            $currentAssets = collect($assets)
                ->filter(fn ($widgetAsset): bool => $existingAssets->has(sprintf('%s.%s', $widgetAsset['asset_type'], $widgetAsset['asset_id'])))
                ->mapWithKeys(fn ($widgetAsset): array => [sprintf('%s.%s', $widgetAsset['asset_type'], $widgetAsset['asset_id']) => $widgetAsset]);

            $assetsToRemove = $currentAssets->isNotEmpty()
                ? $existingAssets->diffKeys($currentAssets)
                : $existingAssets;

            if ($assetsToRemove->isNotEmpty()) {
                $assetsToRemove->each(function (WidgetAsset $widgetAsset) use ($containerKey, $widgetIndex, $widget): void {
                    $widget->assets->forget($widget->assets->search(fn (WidgetAsset $asset): bool => $asset->id === $widgetAsset->id));

                    $this->removeAsset($containerKey, $widgetIndex, $widgetAsset->asset_id, $widgetAsset->asset_type);

                    $widgetAsset->delete();
                });
            }
        }

        if ($assets === []) {
            return;
        }

        collect($assets)->each(
            function (array $widgetAsset) use ($existingAssets, $widget, $containerKey, $occurrence, $hasPageAssets): void {
                $key = sprintf('%s.%s', $widgetAsset['asset_type'], $widgetAsset['asset_id']);

                $order = $widgetAsset['order'];

                $existingAsset = $existingAssets->get($key);

                if ($existingAsset) {
                    $existingAsset->order = $order;
                    $existingAsset->meta = $widgetAsset['meta'] ?? [];
                    $existingAsset->occurrence = $occurrence;

                    if ($hasPageAssets) {
                        $existingAsset->container = $containerKey;
                        $existingAsset->page_id = $this->page_id;
                    } else {
                        $existingAsset->container = null;
                        $existingAsset->page_id = null;
                    }

                    $existingAsset->save();

                    return;
                }

                $this->createWidgetAsset(
                    widget: $widget,
                    containerKey: $containerKey,
                    occurrence: $occurrence,
                    hasPageAssets: $hasPageAssets,
                    order: $order,
                    asset: $widgetAsset,
                );
            },
        );
    }

    private function addWidgetAsset(
        Widget $widget,
        string $containerKey,
        string $type,
        bool $hasPageAssets,
        int|string $assetId,
        array $meta,
        int $occurrence,
        int $order,
    ): WidgetAsset {
        $pageId = $hasPageAssets ? $this->page_id : null;

        $widgetAsset = $widget->assets
            ->where('asset_type', $type)
            ->where('asset_id', $assetId)
            ->where('occurrence', $occurrence)
            ->when(
                $pageId,
                fn (SupportCollection $collection) => $collection->where('container', $containerKey)
                    ->where('page_id', $pageId),
            )
            ->first();

        if (! $widgetAsset) {
            /** @var WidgetAsset $widgetAsset */
            $widgetAsset = $widget->assets()->newModelInstance([
                'meta' => $meta,
                'order' => $order,
                'widget_id' => $widget->id,
                'asset_type' => mb_strtolower($type),
                'asset_id' => $assetId,
                'occurrence' => $occurrence,
            ]);

            if ($pageId !== null && $pageId !== 0) {
                $widgetAsset->page_id = $this->page_id;
                $widgetAsset->container = $containerKey;
            }
        }

        return $widgetAsset;
    }

    private function createWidgetAsset(
        Widget $widget,
        string $containerKey,
        int $occurrence,
        bool $hasPageAssets,
        int $order,
        array $asset,
    ): WidgetAsset {
        // Build the unique key attributes based on whether this is a page-scoped asset
        $attributes = [
            'widget_id' => $widget->id,
            'asset_type' => $asset['asset_type'],
            'asset_id' => $asset['asset_id'],
            'occurrence' => $occurrence,
        ];

        if ($hasPageAssets) {
            $attributes['page_id'] = $this->page_id;
            $attributes['container'] = $containerKey;
        } else {
            $attributes['page_id'] = null;
            $attributes['container'] = null;
        }

        // Try to find an existing asset first to avoid unique constraint violations
        /** @var WidgetAsset|null $existing */
        $existing = WidgetAsset::query()
            ->where($attributes)
            ->first();

        if ($existing) {
            // Update order/meta if needed and return existing record
            $existing->order = $order;
            $existing->meta = $asset['meta'] ?? [];
            $existing->save();

            return $existing;
        }

        /** @var WidgetAsset $widgetAsset */
        $widgetAsset = $widget->assets()->make(array_merge([
            'meta' => $asset['meta'] ?? [],
            'order' => $order,
        ], $attributes));

        $widgetAsset->save();

        return $widgetAsset;
    }

    private function getContainerWidget(string $containerKey, int $widgetIndex): Widget
    {
        if (! property_exists($this, 'containerWidgets') || ! isset($this->containerWidgets[$containerKey][$widgetIndex])) {
            $widget = $this->loadWidget($containerKey, $widgetIndex, withAssets: false);

            $assets = $this->loadWidgetAssetsFor($widget, $containerKey, $widgetIndex);

            $widget->setRelation('assets', $assets);
        }

        return $this->containerWidgets[$containerKey][$widgetIndex];
    }

    private function getWidgetAssets(string $containerKey, int $widgetIndex): array
    {
        return $this->assets[$containerKey][$widgetIndex];
    }

    private function countWidgetAssets(string $containerKey, int $widgetIndex): int
    {
        return count($this->getWidgetAssets($containerKey, $widgetIndex));
    }

    private function getWidgetAsset(string $containerKey, int $widgetIndex, int $index): ?array
    {
        return $this->assets[$containerKey][$widgetIndex][$index] ?? null;
    }

    private function getWidgetAssetsByType(string $containerKey, int $widgetIndex, string $type): array
    {
        if (! isset($this->assets[$containerKey][$widgetIndex])) {
            return [];
        }

        return array_column(
            array_filter($this->assets[$containerKey][$widgetIndex], fn (array $widgetAsset): bool => $widgetAsset['asset_type'] === $type),
            'asset_id',
        );
    }

    private function shouldAddPageAssets(string $containerKey, int $widgetIndex): bool
    {
        if (! $this->inPageContext()) {
            return false;
        }

        $assets = $this->getWidgetAssets($containerKey, $widgetIndex);

        if ($assets === []) {
            return $this->inPageContext();
        }

        return collect($assets)->contains('page_id', $this->page_id);
    }

    private function togglePageAssets(string $containerKey, int $widgetIndex, ?int $pageId): void
    {
        $hasPageAssets = $pageId !== null && $pageId !== 0;

        $this->updatePageAssets($containerKey, $widgetIndex, $hasPageAssets);

        $this->layoutUpdated();
    }

    private function updateWidgetAsset(string $containerKey, int $widgetIndex, int $index, array $data): void
    {
        $widgetAsset = $this->assets[$containerKey][$widgetIndex][$index];

        $this->assets[$containerKey][$widgetIndex][$index] = array_merge_recursive($widgetAsset, $data);
    }

    private function getContainerWidgetSchema(string $containerKey, int $widgetIndex): ?string
    {
        return $this->getContainerWidget($containerKey, $widgetIndex)?->type->admin['layout_container_widget_schema']
            ?? null;
    }

    private function cleanUpWidgetAssets(array $originalContainers): void
    {
        foreach ($originalContainers as $containerKey => $container) {
            foreach ($container['widgets'] as $widgetIndex => $containerWidget) {
                $currentWidget = $this->containers[$containerKey]['widgets'][$widgetIndex] ?? null;

                if (! $currentWidget) {
                    continue;
                }

                $hasPageAssets = $this->hasPageAssets($containerKey, $widgetIndex);

                $occurrence = $containerWidget['occurrence'] ?? $this->getContainerWidgetOccurrence($containerKey, $widgetIndex);

                if ($containerWidget['widget_key'] === $currentWidget['widget_key']) {
                    if (! $hasPageAssets) {
                        continue;
                    }

                    if ($currentWidget['occurrence'] === $occurrence) {
                        continue;
                    }
                }

                $widget = Widget::query()->firstWhere('key', $containerWidget['widget_key']);

                WidgetAsset::query()
                    ->where('widget_id', $widget->id)
                    ->where('occurrence', $occurrence)
                    ->when(
                        $hasPageAssets,
                        fn (Builder $query) => $query->where('page_id', $this->page_id)
                            ->where('container', $containerKey),
                    )
                    ->delete();
            }
        }
    }

    private function inPageContext(): bool
    {
        return $this->page_id && $this->page_id > 0;
    }
}
