<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire;

use Capell\Admin\Actions\BuildWidgetAssetDataAction;
use Capell\Admin\Actions\NotifyClearCachedPagesAction;
use Capell\Admin\Actions\ReplicateLayoutAction;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Concerns\HasPageCacheNotification;
use Capell\Core\Actions\GetResourceFromTypeAction;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Layout\Actions\SaveFormComponentRelationshipAction;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Enums\SchemaTypeEnum;
use Capell\Layout\Filament\Components\Forms\LayoutBuilder\LayoutBuilderAddWidgetSchema;
use Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Containers\DefaultLayoutContainerSchema;
use Capell\Layout\Filament\Resources\Types\Schemas\Types\WidgetTypeSchema;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use ReflectionProperty;

/**
 * @property-read ?Page $page
 */
#[Isolate]
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

    protected ?array $containerWidgets = null;

    protected ?array $cachedAssets = null;

    protected Layout $layout;

    protected ?Page $layoutPage = null;

    protected ?Site $site = null;

    private string $view = 'capell-layout::livewire.layout-builder';

    public function mount(): void
    {
        $this->loadNew();
    }

    public function hydrate(): void
    {
        $this->loadFromStore();
    }

    public function boot(): void
    {
        throw_if(! Filament::auth()->check(), AuthenticationException::class);
    }

    #[On('save-layout')]
    public function saveLayout(bool $withNotifications = false): void
    {
        // Hacky fix to prevent error if builder not loaded and event triggered
        $rp = new ReflectionProperty(self::class, 'layout');

        if (! $rp->isInitialized($this)) {
            $this->loadNew();
        }

        $originalContainers = $this->layout->containers ?? [];

        $this->layout->update([
            'containers' => $this->containers,
        ]);

        if ($this->page_id && $this->getLayoutPage()->layout_id !== $this->layout_id) {
            $this->layoutPage->update([
                'layout_id' => $this->layout_id,
            ]);
        }

        foreach ($this->containers as $containerKey => $container) {
            foreach ($container['widgets'] as $widgetIndex => $widget) {
                $this->updateAssets($containerKey, $widgetIndex);
            }
        }

        if ($this->page_id !== null && $this->page_id !== 0) {
            $this->cleanUpWidgetAssets($originalContainers);
        }

        $this->dispatch('layout-builder-reset');

        $this->layoutUpdated(false);

        if ($withNotifications) {
            Notification::make('layout-saved')
                ->body(__('capell-admin::message.layout_saved'))
                ->success()
                ->send();

            NotifyClearCachedPagesAction::run(
                collect([$this->getLayout()])
                    ->when(
                        $this->getLayoutPage(),
                        fn (Collection $collection, $page): Collection => $collection->push($page)
                    )
            );
        }
    }

    #[On('sync-selected-assets')]
    public function syncSelectedAssets(array $arguments, string $type, array $assets): void
    {
        if (! property_exists($this, 'layout')) {
            return;
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

        $resource = $this->getWidgetAsset($containerKey, $widgetIndex, $index);

        if ($resource === null || $resource === []) {
            throw new Exception(sprintf('Asset %d not found for container: %s widget: %d', $index, $containerKey, $widgetIndex));
        }

        unset($assets[$index]);

        $assets = array_values($assets);

        array_splice($assets, $newIndex, 0, [$resource]);

        $this->assets[$containerKey][$widgetIndex] = $assets;

        $this->loadWidgetAssetsFromStore();

        $this->layoutUpdated();
    }

    public function saveLayoutAction(): Action
    {
        return Action::make('saveLayout')
            ->label(__('capell-admin::button.save_layout'))
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
            ->label(__('capell-admin::button.copy_layout'))
            ->groupedIcon('heroicon-o-square-2-stack')
            ->modalWidth(Width::ScreenSmall)
            ->record(fn (): ?Page => $this->getLayoutPage())
            ->requiresConfirmation()
            ->modalDescription(__('capell-admin::message.copy_layout_confirmation'))
            ->visible(fn (): bool => (bool) $this->page_id)
            ->action(function (Action $action, self $livewire): void {
                $livewire->duplicateLayout();

                $livewire->layoutUpdated();

                $action->success();
            });
    }

    public function addContainerAction(): Action
    {
        return Action::make('addContainer')
            ->label(__('capell-admin::button.container'))
            ->tooltip(__('capell-admin::button.add_container'))
            ->icon('heroicon-m-plus')
            ->color('gray')
            ->outlined()
            ->slideOver()
            ->size(Size::Small)
            ->modalWidth(Width::Large)
            ->modalSubmitActionLabel(fn (Action $action): string => $action->getTooltip())
            ->schema(
                static fn (self $livewire, Schema $schema, array $arguments): Schema => $schema->operation('createOption')
                    ->schema($livewire->getContainerSchema($schema, $arguments))
            )
            ->action(function (Action $action, self $livewire, array $data): void {
                $livewire->saveContainer($data);

                $action->success();
            });
    }

    public function editContainerAction(): Action
    {
        return Action::make('editContainer')
            ->label(__('capell-admin::button.edit_container'))
            ->groupedIcon('heroicon-o-pencil')
            ->size(Size::Small)
            ->color('gray')
            ->grouped()
            ->modalWidth(Width::ScreenLarge)
            ->modalHeading(
                fn (array $arguments): string|array|null => __(
                    'capell-admin::heading.edit_container',
                    ['key' => str($arguments['containerKey'])->title()]
                )
            )
            ->modalSubmitActionLabel(fn (Action $action): string => $action->getLabel())
            ->schema(
                static fn (self $livewire, Schema $schema, array $arguments): Schema => $schema->operation('editOption')
                    ->schema($livewire->getContainerSchema($schema, $arguments))
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
            ->label(__('capell-admin::button.remove_container'))
            ->groupedIcon('heroicon-m-trash')
            ->color('danger')
            ->size(Size::Small)
            ->grouped()
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->removeContainer($arguments['containerKey']);

                $action->success();
            });
    }

    public function editWidgetTypeAction(): Action
    {
        return Action::make('editWidgetType')
            ->label(__('capell-admin::button.edit_widget_type'))
            ->groupedIcon('heroicon-o-pencil')
            ->color('gray')
            ->grouped()
            ->slideOver()
            ->record(
                fn (array $arguments, self $livewire): Type => $livewire->getWidgetType(
                    $arguments['containerKey'],
                    $arguments['widgetIndex']
                )
            )
            ->schema(
                fn (array $arguments, self $livewire, Schema $schema): Schema => $schema->operation('editOption')
                    ->schema(
                        $livewire->getWidgetTypeSchema($schema, $arguments['containerKey'], $arguments['widgetIndex'])
                    )
            )
            ->fillForm(fn (Type $record): array => $record->attributesToArray())
            ->action(function (Action $action, Schema $schema, Type $record, array $data): void {
                $schema->saveRelationships();

                $record->update($data);

                $action->success();
            });
    }

    public function editContainerWidgetAction(): Action
    {
        return Action::make('editContainerWidget')
            ->label(__('capell-admin::button.edit_container_widget'))
            ->groupedIcon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->grouped()
            ->visible(
                fn (array $arguments, self $livewire): bool => (bool) $livewire->getContainerWidgetSchema(
                    $arguments['containerKey'],
                    $arguments['widgetIndex']
                )
            )
            ->modalHeading(__('capell-admin::heading.container_widget_settings'))
            ->modalSubmitActionLabel(fn (Action $action): string => $action->getLabel())
            ->modalDescription(
                fn (array $arguments, self $livewire): string => __(
                    'capell-admin::generic.edit_container_widget',
                    [
                        'container' => $arguments['containerKey'],
                        'widget' => $livewire->getContainerWidget($arguments['containerKey'], $arguments['widgetIndex'])?->name,
                    ]
                )
            )
            ->modalWidth(Width::ScreenSmall)
            ->schema(function (array $arguments, self $livewire, Schema $schema): Schema {
                $adminSchema = CapellAdmin::getSchema(
                    SchemaTypeEnum::LayoutWidget->value,
                    $livewire->getContainerWidgetSchema($arguments['containerKey'], $arguments['widgetIndex'])
                );

                return $schema->operation('editOption')->components(app($adminSchema)::make($schema));
            })
            ->fillForm(
                fn (self $livewire, array $arguments): array => $livewire->containers[$arguments['containerKey']]['widgets'][$arguments['widgetIndex']]['meta'] ?? []
            )
            ->action(function (Action $action, self $livewire, array $arguments, array $data): void {
                $livewire->editContainerWidget($arguments['containerKey'], $arguments['widgetIndex'], $data);

                $action->success();
            });
    }

    public function addWidgetAction(): Action
    {
        return Action::make('addWidget')
            ->label(__('capell-admin::button.widget'))
            ->modalHeading(__('capell-admin::heading.add_widget_to_container'))
            ->modalSubmitActionLabel(__('capell-admin::button.add_widget'))
            ->icon('heroicon-c-plus')
            ->size(Size::Small)
            ->modalWidth(Width::ExtraLarge)
            ->color('gray')
            ->outlined()
            ->closeModalByClickingAway(false)
            ->visible(fn (): bool => (bool) $this->containers)
            ->schema(
                fn (array $arguments, Schema $schema): Schema => $schema->operation('createOption')
                    ->schema(
                        LayoutBuilderAddWidgetSchema::schema(
                            empty($arguments['containerKey']) ? self::getContainerOptions() : null
                        )
                    )
            )
            ->fillForm(function (self $livewire, array $arguments): array {
                /** @var class-string<Widget> $model */
                $model = CapellCore::getModel(LayoutModelEnum::Widget->name);

                return [
                    'container' => $arguments['containerKey'] ?? session('layout-builder.container'),
                    'filter_groups' => collect($model::getTypeGroups()->reject(fn (string $group): bool => $group === 'system'))
                        ->toArray(),
                ];
            })
            ->action(function (Action $action, self $livewire, array $arguments, array $data): void {
                $containerKey = $data['container'] ?? $arguments['containerKey'];

                session(['layout-builder.container' => $containerKey]);

                foreach ($data['widgets'] as $widgetId) {
                    $livewire->saveContainerWidget($containerKey, (int) $widgetId);
                }

                $action->success();
            });
    }

    public function editWidgetAction(): Action
    {
        return Action::make('editWidget')
            ->label(__('capell-admin::button.edit_widget'))
            ->tooltip(__('capell-admin::button.edit_widget'))
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
                    $arguments['widgetIndex']
                )
            )
            ->modalHeading(fn (Widget $record): string => $record->name)
            ->modalDescription(
                fn (Widget $record): string => __(
                    'capell-admin::heading.widget_type',
                    ['type' => $record->type?->name]
                )
            )
            ->modalSubmitActionLabel(__('capell-admin::button.save_changes'))
            ->successNotificationTitle(__('capell-admin::message.widget_updated'))
            ->fillForm(fn (Widget $record): array => $record->attributesToArray())
            ->schema(fn (Schema $schema): Schema => WidgetForm::configure($schema->operation('editOption')))
            ->action(
                function (Action $action, Schema $schema, Widget $record, array $data): void {
                    $schema->mutateDehydratedState($data);

                    $schema->saveRelationships();

                    $record->update($data);

                    $action->success();
                }
            );
    }

    public function duplicateWidgetAction(): Action
    {
        return Action::make('duplicateWidget')
            ->label(__('capell-admin::button.duplicate_widget'))
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
            ->label(__('capell-admin::button.remove_widget'))
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
            ->label(__('capell-admin::button.select_existing'))
            ->grouped()
            ->modal()
            ->extraModalWindowAttributes([
                'class' => 'capell-layout-builder-assets-table',
            ])
            ->icon('heroicon-c-magnifying-glass')
            ->iconSize(IconSize::Small)
            ->size(Size::Small)
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

                $componentName = 'capell-layout::livewire.assets.table.' . $arguments['type'];

                $totalAssets = $livewire->countWidgetAssets($arguments['containerKey'], $arguments['widgetIndex']);

                if ($totalAssets) {
                    $hasPageAssets = $livewire->hasPageAssets($arguments['containerKey'], $arguments['widgetIndex']);
                } else {
                    $hasPageAssets = (bool) $livewire->page_id;
                }

                $existingRecords = $livewire->getWidgetAssetsByType(
                    $arguments['containerKey'],
                    $arguments['widgetIndex'],
                    $arguments['type']
                );

                return new HtmlString(Blade::render(<<<'blade'
                <livewire:is
                    :$actionModalId
                    :component="$componentName"
                    :$arguments
                    :$existingRecords
                 />
            blade, [
                    'actionModalId' => sprintf('fi-%s-action-%s', $livewire->getId(), $action->getNestingIndex()),
                    'componentName' => $componentName,
                    'arguments' => [
                        'containerKey' => $arguments['containerKey'],
                        'widgetIndex' => $arguments['widgetIndex'],
                        'pageId' => $livewire->page_id,
                        'siteId' => $livewire->site_id,
                    ],
                    'existingRecords' => $existingRecords,
                    'hasPageAssets' => $hasPageAssets,
                ]));
            })
            ->submit(null)
            ->modalSubmitAction(false)
            ->modalCancelAction(false);
    }

    public function addAssetAction(): Action
    {
        return Action::make('addAsset')
            ->label(fn (array $arguments): string => __('capell-admin::button.add_new_asset'))
            ->icon('heroicon-o-plus-circle')
            ->iconSize(IconSize::Small)
            ->size(Size::Small)
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
                    ]
                )
            )
            ->modalSubmitActionLabel(
                fn (array $arguments, Action $action): string => __(
                    'capell-admin::button.create_widget_asset',
                    ['type' => $arguments['type']]
                )
            )
            ->successNotificationTitle(__('capell-admin::message.asset_added'))
            ->schema(
                fn (array $arguments, Schema $schema): Schema => self::getWidgetAssetSchema(
                    $schema->operation('createOption')
                        ->record(function () use ($arguments): WidgetAsset {
                            $containerKey = $arguments['containerKey'];
                            $widgetIndex = $arguments['widgetIndex'];
                            $assetType = $arguments['type'];

                            $widget = $this->getContainerWidget($containerKey, $widgetIndex);

                            /** @var class-string<WidgetAsset> $model */
                            $model = CapellCore::getModel(LayoutModelEnum::WidgetAsset->name);

                            $record = $model::make([
                                'widget_id' => $widget->id,
                                'asset_type' => $arguments['type'],
                                'meta' => [],
                            ]);

                            $asset = CapellCore::getAsset($assetType)->getModel()::make();

                            $record->setRelation('asset', $asset);

                            return $record;
                        }),
                )
            )
            ->model(fn (): string => CapellCore::getModel(LayoutModelEnum::WidgetAsset->name))
            ->fillForm(function (array $arguments): array {
                $containerKey = $arguments['containerKey'];
                $widgetIndex = $arguments['widgetIndex'];
                $assetType = $arguments['type'];

                $widget = $this->getContainerWidget($containerKey, $widgetIndex);

                return [
                    'widget_id' => $widget->id,
                    'asset_type' => $assetType,
                    'meta' => [],
                    'asset' => BuildWidgetAssetDataAction::run($arguments['type']),
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
            ->icon(fn (array $arguments): string => CapellCore::getAsset($arguments['type'])->getIcon())
            ->iconSize(IconSize::Small)
            ->tooltip(
                fn (array $arguments): string => __(
                    'capell-admin::button.edit_resource',
                    ['type' => $arguments['type']]
                )
            )
            ->modalWidth(Width::SixExtraLarge)
            ->modalHeading(
                function (self $livewire, array $arguments): string {
                    $name = str($arguments['type'])->title();

                    if ($livewire->page_id !== null && $livewire->page_id !== 0) {
                        return __(
                            'capell-admin::heading.edit_page_widget_asset',
                            ['name' => $name]
                        );
                    }

                    return __(
                        'capell-admin::heading.edit_widget_asset',
                        ['name' => $name]
                    );
                }
            )
            ->modalDescription(
                function (self $livewire, array $arguments): ?string {
                    if ($livewire->page_id === null || $livewire->page_id === 0) {
                        return null;
                    }

                    $resource = $this->getWidgetAsset($arguments['containerKey'], $arguments['widgetIndex'], $arguments['index']);

                    if (empty($resource['page_id'])) {
                        return null;
                    }

                    return __('capell-admin::heading.page_widget_asset', ['name' => $livewire->getLayoutPage()->name]);
                }
            )
            ->modalSubmitActionLabel(__('capell-admin::button.save_changes'))
            ->successNotificationTitle(__('capell-admin::message.asset_updated'))
            ->schema(
                fn (self $livewire, Schema $schema, array $arguments): Schema => self::getWidgetAssetSchema(
                    $schema->operation('editOption')
                )
            )
            ->fillForm(fn (WidgetAsset $record, array $arguments): array => [
                'meta' => $record->meta,
                'asset' => $record->asset->attributesToArray(),
            ])
            ->record(function (array $arguments): ?WidgetAsset {
                $widget = $this->getContainerWidget($arguments['containerKey'], $arguments['widgetIndex']);

                $asset = $this->getWidgetAsset($arguments['containerKey'], $arguments['widgetIndex'], $arguments['index']);

                if (! $asset) {
                    return null;
                }

                $widgetAsset = $widget->assets
                    ->where('asset_type', $arguments['type'])
                    ->where('asset_id', $asset['asset_id'])
                    ->first();

                if (! $widgetAsset) {
                    throw new Exception(
                        'Widget asset not found for container: ' . $arguments['containerKey'] .
                        ' widget: ' . $arguments['widgetIndex'] .
                        ' (key: ' . ($widget->key ?? 'unknown') . ')' .
                        ' index: ' . $arguments['index']
                    );
                }

                return $widgetAsset->exists ? $widgetAsset : null;
            })
            ->disabled(fn (WidgetAsset $record): bool => ! $record->exists)
            ->action(function (WidgetAsset $record, array $data, self $livewire, array $arguments, Action $action, Schema $schema): void {
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
                    sprintf("selectedRecords['%s'][%s].length", $arguments['containerKey'], $arguments['widgetIndex'])
                ),
            ])
            ->action(function (self $livewire, array $arguments, Action $action): void {
                $selectedAssets = $livewire->getSelectedAssets($arguments['containerKey'], $arguments['widgetIndex']);

                if ($selectedAssets === []) {
                    Notification::make('no-assets-selected')
                        ->body(__('capell-admin::message.no_resources_selected'))
                        ->warning()
                        ->send();

                    $action->halt();
                }

                $livewire->removeSelectedAssets($arguments['containerKey'], $arguments['widgetIndex']);
            });
    }

    public function changeLayoutAction(): Action
    {
        return Action::make('changeLayout')
            ->label(__('capell-admin::button.change'))
            ->tooltip(__('capell-admin::button.change_layout'))
            ->button()
            ->size(Size::ExtraSmall)
            ->icon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->modalHeading(__('capell-admin::button.change_layout'))
            ->modalWidth(Width::Small)
            ->visible(fn (): bool => (bool) $this->page_id)
            ->schema(
                fn (Schema $schema, self $livewire): Schema => $schema->operation('editOption')
                    ->schema($livewire->getChangeLayoutSchema())
            )
            ->fillForm(fn (self $livewire): array => ['layout_id' => $livewire->layout_id])
            ->modalSubmitActionLabel(__('capell-admin::button.change_layout'))
            ->action(function (self $livewire, Action $action, array $data): void {
                $livewire->changePageLayout($data['layout_id']);

                $this->dispatch('page-layout-changed', id: $data['layout_id']);

                $action->success();
            });
    }

    public function convertPageAssetsAction(): Action
    {
        return Action::make('convertPageAssets')
            ->label(
                function (self $livewire, array $arguments): string {
                    $hasPageAssets = $livewire->hasPageAssets(
                        containerKey: $arguments['containerKey'],
                        widgetIndex: $arguments['widgetIndex']
                    );

                    return $hasPageAssets
                        ? __('capell-admin::button.convert_widget_assets')
                        : __('capell-admin::button.convert_page_assets');
                }
            )
            ->grouped()
            ->icon('heroicon-o-arrows-right-left')
            ->color('gray')
            ->size(Size::ExtraSmall)
            ->visible(function (self $livewire, array $arguments): bool {
                if ($livewire->page_id === null || $livewire->page_id === 0) {
                    return false;
                }

                $hasPageAssets = $livewire->hasPageAssets(
                    containerKey: $arguments['containerKey'],
                    widgetIndex: $arguments['widgetIndex']
                );

                if (! $hasPageAssets) {
                    return true;
                }

                $widget = $livewire->getContainerWidget($arguments['containerKey'], $arguments['widgetIndex']);

                return ! $widget->widgetAssets()->exists();
            })
            ->requiresConfirmation()
            ->modalDescription(
                function (self $livewire, array $arguments): string {
                    $hasPageAssets = $livewire->hasPageAssets(
                        containerKey: $arguments['containerKey'],
                        widgetIndex: $arguments['widgetIndex']
                    );

                    return $hasPageAssets
                        ? __('capell-admin::generic.convert_widget_assets')
                        : __('capell-admin::generic.convert_page_assets');
                }
            )
            ->action(function (self $livewire, array $arguments, Action $action): void {
                $hasPageAssets = $livewire->hasPageAssets(
                    containerKey: $arguments['containerKey'],
                    widgetIndex: $arguments['widgetIndex']
                );

                $livewire->togglePageAssets(
                    $arguments['containerKey'],
                    $arguments['widgetIndex'],
                    pageId: $hasPageAssets ? null : $livewire->page_id
                );

                $action->success();
            });
    }

    public function selectAllAssets(string $containerKey, int $widgetIndex): void
    {
        $this->selectedRecords[$containerKey][$widgetIndex] = $this->getAllSelectableAssetsKeys(
            $containerKey,
            $widgetIndex
        );
    }

    public function deSelectAllAssets(string $containerKey, int $widgetIndex): void
    {
        $this->selectedRecords[$containerKey][$widgetIndex] = [];
    }

    public function getLayout(): Layout
    {
        return $this->layout;
    }

    public function getLayoutPagesCount(): int
    {
        if (! property_exists($this->layout, 'pages_count')) {
            $this->layout->loadCount('pages');
        }

        return $this->layout->pages_count;
    }

    public function hasPageAssets(string $containerKey, int $widgetIndex): bool
    {
        if ($this->page_id === null || $this->page_id === 0) {
            return false;
        }

        $assets = $this->getWidgetAssets($containerKey, $widgetIndex);

        if ($assets === []) {
            return false;
        }

        return collect($assets)->contains('page_id', $this->page_id);
    }

    #[Computed]
    public function page(): ?Page
    {
        if ($this->page_id) {
            return Page::find($this->page_id);
        }

        return null;
    }

    /**
     * @return class-string<resource>
     */
    public function getPageResource(): string
    {
        return $this->page ? GetResourceFromTypeAction::run($this->page) : CapellAdmin::getResource(ResourceEnum::Page);
    }

    /**
     * @return class-string<resource>
     */
    public function getResource(): string
    {
        if ($this->page_id) {
            return $this->getPageResource();
        }

        return CapellAdmin::getResource(ResourceEnum::Layout);
    }

    public function placeholder(array $params = []): View
    {
        return view('capell-admin::components.placeholder', $params);
    }

    public function render(): string|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
    {
        if (! isset($this->layout)) {
            $this->skipRender();

            return '<div></div>';
        }

        return view($this->view);
    }

    protected function moveContainerWidget(string $originalContainer, int $originalIndex, string $containerKey, int $widgetIndex): void
    {
        $widget = $this->containers[$originalContainer]['widgets'][$originalIndex];

        if ($originalContainer !== $containerKey) {
            $widget['occurrence'] = $this->getLastContainerWidgetOccurrence(
                containerKey: $containerKey,
                widgetKey: $widget['widget_key'],
                widgets: $this->containers[$containerKey]['widgets']
            ) + 1;
        }

        $widgets = $this->containers[$originalContainer]['widgets'];

        unset($widgets[$originalIndex]);

        $this->containers[$originalContainer]['widgets'] = array_values($widgets);

        $widgets = $this->containers[$containerKey]['widgets'];
        $widgets = array_merge(array_slice($widgets, 0, $widgetIndex), [$widget], array_slice($widgets, $widgetIndex));
        $this->containers[$containerKey]['widgets'] = $widgets;

        $widget = $this->containerWidgets[$originalContainer][$originalIndex];

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

    protected function addAssetFromAction(Action $action, Schema $schema, array $arguments, array $data): void
    {
        $shouldAddPageAssets = $this->shouldAddPageAssets($arguments['containerKey'], $arguments['widgetIndex']);

        $widget = $this->getContainerWidget($arguments['containerKey'], $arguments['widgetIndex']);

        $record = $schema->getRecord();

        // Fake exists to ensure assets relations are saved correctly
        $record->exists = true;

        $data['widget_id'] = $widget->id;

        $assetComponent = $schema->getComponentByStatePath('asset');

        SaveFormComponentRelationshipAction::run($assetComponent, $action->getLivewire());

        $record->exists = false;
        $record->fill($data)->save();

        $uuid = $record->getKey();

        $this->addAssets(
            containerKey: $arguments['containerKey'],
            widgetIndex: $arguments['widgetIndex'],
            hasPageAssets: $shouldAddPageAssets,
            type: $arguments['type'],
            assets: [$uuid],
            assetsMeta: [$uuid => $data['meta'] ?? []]
        );

        $this->syncDuplicateWidgets(
            containerKey: $arguments['containerKey'],
            widgetIndex: $arguments['widgetIndex']
        );

        $this->layoutUpdated();

        $this->dispatch(
            'refresh-assets',
            containerKey: $arguments['containerKey'],
            widgetIndex: $arguments['widgetIndex']
        );
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

    private function getChangeLayoutSchema(): array
    {
        return [
            Select::make('layout_id')
                ->label(__('capell-admin::form.layout'))
                ->required()
                ->searchable()
                ->options(
                    Layout::query()
                        ->withCount('pages')
                        ->when(
                            $this->site_id,
                            fn (Builder $query, int $siteId): Builder => $query->where(
                                fn (Builder $query) => $query->where('site_id', $siteId)
                                    ->orWhereNull('site_id')
                            )
                        )
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(
                            fn (Layout $layout): array => [$layout->id => $layout->name . ' (' . $layout->pages_count . ')']
                        )
                        ->toArray()
                )
                ->default(fn () => CapellCore::getModel(ModelEnum::Layout)::default()->first(['id'])?->id)
                ->reactive()
                ->helperText(
                    function (?int $state): ?HtmlString {
                        if ($state === null || $state === 0) {
                            return null;
                        }

                        $total = Layout::find($state)->pages()->count();

                        return new HtmlString(
                            trans_choice(
                                'capell-admin::message.layout_count_on_pages',
                                $total,
                                [
                                    'count' => $total,
                                    'url' => CapellAdmin::getResource(ResourceEnum::Page)::getUrl(
                                        'index',
                                        ['tableFilters' => ['layout_id' => ['value' => $state]]]
                                    ),
                                ]
                            )
                        );
                    }
                ),
        ];
    }

    private function loadNew(): void
    {
        $this->loadLayout();

        $this->setupContainers();

        $this->loadWidgets();

        foreach (array_keys($this->containers) as $containerKey) {
            $this->setupContainerWidgets($containerKey);
        }

        $this->setupSelectedAssets();
    }

    private function loadFromStore(): void
    {
        $this->loadLayout();

        $this->loadWidgets();

        $this->loadWidgetAssetsFromStore();
    }

    private function reload(): void
    {
        $this->loadLayout();

        $this->containers = null;
        $this->containerWidgets = null;
        $this->assets = [];
        $this->cachedAssets = [];

        $this->loadNew();
    }

    private function getSelectedAssets(string $containerKey, int $widgetIndex): array
    {
        return $this->selectedRecords[$containerKey][$widgetIndex] ?? [];
    }

    private function getAllSelectableAssetsKeys(string $containerKey, int $widgetIndex): array
    {
        return collect($this->assets[$containerKey][$widgetIndex])
            ->map(fn ($resource): string => sprintf('%s.%s', $resource['asset_type'], $resource['asset_id']))
            ->values()
            ->all();
    }

    private function loadLayout(): Layout
    {
        /** @var class-string<Layout> $model */
        $model = CapellCore::getModel(ModelEnum::Layout);

        $layout = $model::withCount('pages')->find($this->layout_id);

        if (! $layout) {
            throw new Exception('Layout not found');
        }

        $this->layout = $layout;

        return $this->layout;
    }

    private function saveContainer(array $data, ?string $key = null): void
    {
        if ($key === null || $key === '' || $key === '0') {
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

    private function saveContainerWidget(string $containerKey, int $widgetId, ?int $index = null): void
    {
        if ($index === null || $index === 0) {
            $widget = $this->getWidget($widgetId);

            $this->addContainerWidget($containerKey, $widget);
        }

        $this->layoutUpdated();
    }

    private function addContainerWidget(string $containerKey, Widget $widget): int
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

        $this->containerWidgets[$containerKey][] = $this->containerWidgets[$containerKey][$originalIndex];
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
        $widgetAssets = $this->containerWidgets[$containerKey][$widgetIndex]->assets
            ->reject(
                fn (WidgetAsset $resource): bool => in_array(
                    sprintf('%s.%s', $resource->asset_type, $resource->asset_id),
                    $this->selectedRecords[$containerKey][$widgetIndex],
                    true
                )
            );

        foreach ($this->selectedRecords[$containerKey][$widgetIndex] as $asset) {
            [$type, $uuid] = explode('.', (string) $asset);

            $this->removeAsset($containerKey, $widgetIndex, $uuid, $type);
        }

        $this->selectedRecords[$containerKey][$widgetIndex] = [];

        $this->assets[$containerKey][$widgetIndex] = array_values($this->assets[$containerKey][$widgetIndex]);

        $this->containerWidgets[$containerKey][$widgetIndex]->setRelation('assets', $widgetAssets);

        $this->layoutUpdated();
    }

    private function removeAsset(string $containerKey, int $widgetIndex, string $uuid, string $type): void
    {
        foreach ($this->assets[$containerKey][$widgetIndex] as $index => $resource) {
            if ($resource['asset_id'] !== $uuid) {
                continue;
            }

            if ($resource['asset_type'] !== $type) {
                continue;
            }

            unset($this->assets[$containerKey][$widgetIndex][$index]);
        }
    }

    private function editContainerWidget(string $containerKey, int $widgetIndex, array $data): void
    {
        $this->containers[$containerKey]['widgets'][$widgetIndex]['meta'] = array_merge(
            $this->containers[$containerKey]['widgets'][$widgetIndex]['meta'] ?? [],
            $data
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

        $occurrence = 0;

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
        if ($this->page_id === null || $this->page_id === 0) {
            return null;
        }

        if ($this->layoutPage instanceof Page) {
            return $this->layoutPage;
        }

        /** @var class-string<Page> $model */
        $model = CapellCore::getModel(ModelEnum::Page);

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
        $model = CapellCore::getModel(ModelEnum::Site);

        return $model::find($this->site_id);
    }

    private function setupContainers(): void
    {
        if ($this->containers !== null) {
            return;
        }

        $this->containers = [];

        if (! $this->layout->containers) {
            return;
        }

        foreach ($this->layout->containers as $key => $container) {
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

    private function setupContainerWidgets(string $containerKey): void
    {
        $container = $this->containers[$containerKey];

        $widgetOccurrences = [];

        foreach ($container['widgets'] as $widgetIndex => $widget) {
            if (! isset($widgetOccurrences[$widget['widget_key']])) {
                $widgetOccurrences[$widget['widget_key']] = 1;
            } else {
                $widgetOccurrences[$widget['widget_key']]++;
            }

            $this->containers[$containerKey]['widgets'][$widgetIndex]['occurrence'] = $widgetOccurrences[$widget['widget_key']];

            $widgetAssets = [];

            if ($this->containerWidgets[$containerKey][$widgetIndex]->assets->isNotEmpty()) {
                $widgetAssets = $this->containerWidgets[$containerKey][$widgetIndex]
                    ->assets
                    ->map(fn (WidgetAsset $resource): array => $resource->attributesToArray())
                    ->toArray();
            }

            $this->assets[$containerKey][$widgetIndex] = $widgetAssets;

            $this->updatePageAssets($containerKey, $widgetIndex);
        }
    }

    private function getContainerSchema(Schema $schema, array $arguments): array
    {
        $containerKey = $arguments['containerKey'] ?? null;

        $adminSchema = CapellAdmin::getSchema(
            SchemaTypeEnum::LayoutContainer->value,
            $this->layout->admin['container_schema'][$containerKey] ?? DefaultLayoutContainerSchema::getKey()
        );

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
                        str($state)->slug()->lower()->toString()
                    )
                )
                ->dehydrateStateUsing(fn ($state): string => str($state)->slug()->lower()->toString())
                ->rules([
                    fn (LayoutBuilder $livewire): Closure => function (string $attribute, $value, Closure $fail) use ($livewire, $containerKey): void {
                        if (! isset($livewire->containers[$value]) || ($containerKey && $containerKey === $value)) {
                            return;
                        }

                        $fail(__('capell-admin::message.layout_container_key_not_unique', ['key' => $value]));
                    },
                ]),
            ...app($adminSchema)::make($schema),
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
    private function getWidgetQuery(): Builder
    {
        /** @var class-string<Widget> $model */
        $model = CapellCore::getModel(LayoutModelEnum::Widget->name);

        return $model::query()
            ->withCount(['layouts'])
            ->with([
                'type',
                'backgroundImage',
                'image',
                'translation' => fn (BuilderContract $query) => $query->orderBy('language_id'),
                'assets' => fn (BuilderContract $query) => $query->when(
                    $this->page_id,
                    fn (Builder $query) => $query->where(
                        fn (Builder $query) => $query->where('page_id', $this->page_id)
                            ->orWhereNull('page_id')
                    ),
                    fn (Builder $query) => $query->whereNull('page_id')
                ),
            ]);
    }

    private function loadWidgets(): void
    {
        $widgetKeys = $this->getContainerWidgetKeys();

        $widgets = $this->getWidgetQuery()
            ->whereIn('key', $widgetKeys)
            ->with([
                'assets' => fn (BuilderContract $query) => $query->when(
                    $this->page_id,
                    fn (Builder $query) => $query->where(
                        fn (Builder $query) => $query->where('page_id', $this->page_id)
                            ->orWhereNull('page_id')
                    ),
                    fn (Builder $query) => $query->whereNull('page_id')
                )
                    ->with(
                        'asset',
                        fn (BuilderContract $query) => $query->morphWith($this->getAssetRelations())
                    ),
            ])
            ->get()
            ->mapWithKeys(fn (Widget $widget): array => [$widget->key => $widget]);

        foreach ($this->containers as $containerKey => $container) {
            foreach ($container['widgets'] as $widgetIndex => $containerWidget) {
                if (! isset($widgets[$containerWidget['widget_key']])) {
                    Notification::make('widget-not-found')
                        ->title(__('capell-admin::generic.widget_not_found'))
                        ->body(__('capell-admin::message.widget_not_found', ['name' => $containerWidget['widget_key']]))
                        ->warning()
                        ->send();

                    unset($this->containers[$containerKey]['widgets'][$widgetIndex]);

                    continue;
                }

                $widgetKey = $containerWidget['widget_key'];
                $occurrence = $containerWidget['occurrence'] ?? 1;

                $widget = clone $widgets[$widgetKey];

                $assets = $widget->assets->filter(
                    function (WidgetAsset $resource) use ($containerKey, $occurrence): bool {
                        if (! $resource->page_id) {
                            return true;
                        }

                        if ($resource->page_id !== $this->page_id) {
                            return false;
                        }

                        if ((string) $resource->container !== (string) $containerKey) {
                            return false;
                        }

                        return (int) $resource->occurrence === (int) $occurrence;
                    }
                )
                    ->sortBy('order')
                    ->values();

                $widget->setRelation('assets', $assets);

                $this->containerWidgets[$containerKey][$widgetIndex] = $widget;
            }
        }
    }

    private function getContainerOptions(): \Illuminate\Support\Collection
    {
        return collect($this->containers)
            ->keys()
            ->mapWithKeys(fn (string $container): array => [$container => __($container)]);
    }

    private function getAssetRelations(?string $type = null): array
    {
        $relations = [
            'content' => ['image', 'media', 'related', 'translation', 'site'],
            'page' => ['image', 'translation', 'site', 'type', 'descendants'],
        ];

        if ($type === null) {
            return $relations;
        }

        return $relations[$type] ?? [];
    }

    private function loadMorphAssetRelations(Collection $widgets): Collection
    {
        $widgetAssets = $widgets->pluck('assets')
            ->flatten()
            ->filter()
            ->groupBy('asset_type')
            ->each(
                fn ($models, string $morphType): Collection => Collection::make($models)
                    ->load([
                        'asset' => fn (BuilderContract $query) => $query->with(
                            $this->getAssetRelations($morphType)
                        ),
                    ])
            );

        return $widgets->each(
            fn (Widget $widget) => $widget->setRelation(
                'assets',
                $widget->assets->map(
                    function (WidgetAsset $resource) use ($widgetAssets) {
                        $asset = $widgetAssets[$resource->asset_type]
                            ->firstWhere('asset_id', $resource->asset_id)
                            ->asset;

                        return $resource->setRelation('asset', $asset);
                    }
                )
            )
        );
    }

    private function loadWidgetAssetsFromStore(): void
    {
        if ($this->assets === null || $this->assets === []) {
            return;
        }

        $allAssets = collect($this->assets)
            ->flatten(2)
            ->filter()
            ->groupBy('asset_type')
            ->mapWithKeys(
                fn (\Illuminate\Support\Collection $assets, string $type): array => [
                    $type => CapellCore::getAsset($type)->model::query()
                        ->with($this->getAssetRelations($type))
                        ->whereIn('id', $assets->pluck('asset_id')->unique()->toArray())
                        ->get()
                        ->keyBy('id'),
                ]
            );

        foreach ($this->assets as $containerKey => $widgets) {
            foreach ($widgets as $widgetIndex => $assets) {
                if (! isset($this->containerWidgets[$containerKey][$widgetIndex])) {
                    continue;
                }

                $widget = $this->getContainerWidget($containerKey, $widgetIndex);

                $widgetAssets = collect();

                foreach ($assets as $order => $resource) {
                    $type = $resource['asset_type'];

                    if (! isset($allAssets[$type][$resource['asset_id']])) {
                        continue;
                    }

                    $asset = $allAssets[$type][$resource['asset_id']];

                    $widgetAsset = $this->addWidgetAsset(
                        widget: $widget,
                        containerKey: $containerKey,
                        type: $type,
                        pageId: $resource['page_id'],
                        resourceUuid: $resource['asset_id'],
                        meta: $resource['meta'] ?? [],
                        occurrence: $resource['occurrence'],
                        order: $order,
                    );

                    $widgetAsset->setRelation('asset', $asset);

                    $widgetAssets->add($widgetAsset);
                }

                $widget->setRelation('assets', $widgetAssets);
            }
        }
    }

    private function reloadContainerWidgetAsset(string $containerKey, int $widgetIndex, int $index): void
    {
        $widget = $this->getContainerWidget($containerKey, $widgetIndex);

        $widget->assets[$index]->fresh();
    }

    /**
     * @throws Exception
     */
    private function getWidget(int $id): \Capell\Layout\Models\Widget
    {
        $widget = $this->getWidgetQuery()->find($id);

        if (! $widget) {
            throw new Exception(sprintf("Unable to find '%d' widget", $id));
        }

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

    private function addAssets(string $containerKey, int $widgetIndex, ?bool $hasPageAssets, string $type, array $assets, array $assetsMeta = []): void
    {
        if (! isset($this->assets[$containerKey][$widgetIndex])) {
            $this->assets[$containerKey][$widgetIndex] = [];
        }

        $widget = $this->containerWidgets[$containerKey][$widgetIndex];

        $occurrence = $this->getLastContainerWidgetOccurrence($containerKey, $widget->key);

        foreach ($assets as $resourceUuid) {
            $resource = $this->getWidgetAssetRecord(ucfirst($type), $resourceUuid);

            $meta = $assetsMeta[$resourceUuid] ?? [];

            $order = $this->countWidgetAssets($containerKey, $widgetIndex) + 1;

            $this->assets[$containerKey][$widgetIndex][] = [
                'asset_id' => $resourceUuid,
                'asset_type' => $type,
                'meta' => $meta,
                'page_id' => $hasPageAssets === true ? $this->page_id : null,
                'widget_id' => $widget->id,
                'occurrence' => $occurrence,
            ];

            $widgetAsset = $this->addWidgetAsset(
                widget: $widget,
                containerKey: $containerKey,
                type: $type,
                pageId: $hasPageAssets === true ? $this->page_id : null,
                resourceUuid: $resourceUuid,
                meta: $meta,
                occurrence: $occurrence,
                order: $order,
            );

            $widgetAsset->setRelation('asset', $resource);

            $widget->assets->add($widgetAsset);
        }

        $this->containerWidgets[$containerKey][$widgetIndex] = $widget;
    }

    private function updateAssets(string $containerKey, int $widgetIndex): void
    {
        $widget = $this->getContainerWidget($containerKey, $widgetIndex);

        $occurrence = $this->getContainerWidgetOccurrence($containerKey, $widgetIndex);

        $assets = $this->assets[$containerKey][$widgetIndex] ?? [];

        $hasPageAssets = $assets ? $this->hasPageAssets($containerKey, $widgetIndex) : (bool) $this->page_id;

        $this->syncAssets($widget, $containerKey, $occurrence, $assets, $hasPageAssets);
    }

    private function syncAssets(Widget $widget, string $containerKey, int $occurrence, array $assets, bool $hasPageAssets): void
    {
        $existingAssets = $widget->assets()
            ->when(
                $hasPageAssets,
                fn (Builder $query) => $query
                    ->where('container', $containerKey)
                    ->where('occurrence', $occurrence)
                    ->where('page_id', $this->page_id),
                fn (Builder $query) => $query->whereNull('page_id')
            )
            ->get()
            ->mapWithKeys(fn (WidgetAsset $resource): array => [$resource->asset_key => $resource]);

        if ($existingAssets->isNotEmpty()) {
            $currentAssets = collect($assets)
                ->filter(fn ($resource): bool => $existingAssets->has(sprintf('%s.%s', $resource['asset_type'], $resource['asset_id'])))
                ->mapWithKeys(fn ($resource): array => [sprintf('%s.%s', $resource['asset_type'], $resource['asset_id']) => $resource]);

            $assetsToRemove = $currentAssets->isNotEmpty()
                ? $existingAssets->diffKeys($currentAssets)
                : $existingAssets;

            if ($assetsToRemove->isNotEmpty()) {
                $assetsToRemove->each->delete();
            }
        }

        $order = 0;
        collect($assets)->each(
            function (array $resource) use ($existingAssets, $widget, $containerKey, $occurrence, $hasPageAssets, &$order): void {
                $order++;

                $key = sprintf('%s.%s', $resource['asset_type'], $resource['asset_id']);

                $existingAsset = $existingAssets->get($key);

                if ($existingAsset) {
                    if ($existingAsset->order !== $order) {
                        $existingAsset->order = $order;
                    }

                    if ($hasPageAssets && $existingAsset->container !== $containerKey) {
                        $existingAsset->container = $containerKey;
                    }

                    $existingAsset->save();

                    return;
                }

                $this->addAsset(
                    widget: $widget,
                    containerKey: $containerKey,
                    occurrence: $occurrence,
                    hasPageAssets: $hasPageAssets,
                    order: $order,
                    resource: $resource
                );
            }
        );
    }

    private function addWidgetAsset(
        Widget $widget,
        string $containerKey,
        string $type,
        ?int $pageId,
        int|string $resourceUuid,
        array $meta,
        ?int $occurrence,
        int $order,
    ): WidgetAsset {
        $widgetAsset = $widget->assets()
            ->where('asset_type', $type)
            ->where('asset_id', $resourceUuid)
            ->when(
                $pageId,
                fn (Builder $query) => $query->where('container', $containerKey)
                    ->where('occurrence', $occurrence)
                    ->where('page_id', $pageId)
            )
            ->first();

        if (! $widgetAsset) {
            /** @var WidgetAsset $widgetAsset */
            $widgetAsset = $widget->assets()->newModelInstance([
                'meta' => $meta,
                'order' => $order,
                'widget_id' => $widget->id,
                'asset_type' => mb_strtolower($type),
                'asset_id' => $resourceUuid,
            ]);

            if ($pageId !== null && $pageId !== 0) {
                $widgetAsset->page_id = $this->page_id;
                $widgetAsset->container = $containerKey;
                $widgetAsset->occurrence = $occurrence;
            }
        }

        $widgetAsset->load([
            'asset' => fn (BuilderContract $query) => $query->with($this->getAssetRelations($type)),
        ]);

        return $widgetAsset;
    }

    private function addAsset(
        Widget $widget,
        string $containerKey,
        int $occurrence,
        bool $hasPageAssets,
        int $order,
        array $resource,
    ): WidgetAsset {
        /** @var WidgetAsset $widgetAsset */
        $widgetAsset = $widget->assets()->make([
            'meta' => $resource['meta'] ?? [],
            'order' => $order,
            'widget_id' => $widget->id,
            'asset_type' => $resource['asset_type'],
            'asset_id' => $resource['asset_id'],
        ]);

        if ($hasPageAssets) {
            $widgetAsset->page_id = $this->page_id;
            $widgetAsset->container = $containerKey;
            $widgetAsset->occurrence = $occurrence;
        }

        $widgetAsset->save();

        // $widgetAsset->load('asset');

        return $widgetAsset;
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

    private function duplicateLayout(): void
    {
        $newLayout = ReplicateLayoutAction::run($this->layout);

        $this->layout_id = $newLayout->id;

        $this->reload();

        $this->dispatch('page-layout-changed', id: $this->layout_id);
    }

    private function getContainerWidget(string $containerKey, int $widgetIndex): Widget
    {
        return $this->containerWidgets[$containerKey][$widgetIndex];
    }

    private function getWidgetType(string $containerKey, int $widgetIndex): ?Type
    {
        return $this->getContainerWidget($containerKey, $widgetIndex)?->type;
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

    private function getWidgetAssetsByType(string $containerKey, int $widgetIndex, string $type): ?array
    {
        if (! isset($this->assets[$containerKey][$widgetIndex])) {
            return null;
        }

        return array_column(
            array_filter($this->assets[$containerKey][$widgetIndex], fn (array $resource): bool => $resource['asset_type'] === $type),
            'asset_id'
        );
    }

    private function getWidgetAssetRecord(string $type, int|string $id): ?Model
    {
        if (isset($this->cachedAssets[$type][$id])) {
            return $this->cachedAssets[$type][$id];
        }

        /** @var Model $model */
        $model = CapellCore::getAsset($type)->model;

        $this->cachedAssets[$type][$id] = $model::find($id);

        if (! $this->cachedAssets[$type][$id]) {
            return null;
        }

        return $this->cachedAssets[$type][$id];
    }

    private function shouldAddPageAssets(string $containerKey, int $widgetIndex): bool
    {
        if ($this->page_id === null || $this->page_id === 0) {
            return false;
        }

        $assets = $this->getWidgetAssets($containerKey, $widgetIndex);

        if ($assets === []) {
            return (bool) $this->page_id;
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
        $resource = $this->assets[$containerKey][$widgetIndex][$index];

        $this->assets[$containerKey][$widgetIndex][$index] = array_merge_recursive($resource, $data);
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

                $widget = Widget::firstWhere('key', $containerWidget['widget_key']);

                WidgetAsset::query()
                    ->where('widget_id', $widget->id)
                    ->when(
                        $hasPageAssets,
                        fn (Builder $query) => $query->where('page_id', $this->page_id)
                            ->where('container', $containerKey)
                            ->where('occurrence', $occurrence)
                    )
                    ->delete();
            }
        }
    }

    private function syncDuplicateWidgets(string $containerKey, int $widgetIndex): void
    {
        $assets = $this->getWidgetAssets($containerKey, $widgetIndex);

        foreach ($this->containers as $_containerKey => $container) {
            foreach ($container['widgets'] as $index => $widget) {
                if ($_containerKey === $containerKey && $index === $widgetIndex) {
                    continue;
                }

                if ($widget['widget_key'] !== $this->containers[$containerKey]['widgets'][$widgetIndex]['widget_key']) {
                    continue;
                }

                $this->assets[$containerKey][$index] = $assets;
            }
        }
    }

    private function getWidgetTypeSchema(Schema $schema, string $containerKey, int $widgetIndex): array
    {
        $name = $this->getContainerWidget($containerKey, $widgetIndex)
            ?->type
            ?->admin['type_schema']
            ?? WidgetTypeSchema::getKey();

        $adminSchema = CapellAdmin::getSchema(\Capell\Admin\Enums\SchemaTypeEnum::Type->value, $name);

        return app($adminSchema)::make($schema);
    }
}
