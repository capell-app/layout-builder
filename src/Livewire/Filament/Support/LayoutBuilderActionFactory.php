<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Support;

use BackedEnum;
use Capell\Admin\Actions\ReplicateLayoutAction;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\HtmlCache\Actions\ClearCachedUrlsForModelAction;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Exceptions\MissingWidgetAssetException;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Resources\Pages\Tables\PageSelectionTable;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Schemas\WidgetAssetForm;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Schemas\WidgetForm;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Tables\WidgetSelectionTable;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\PublishingStudio\Actions\CreateRecordDraftWorkspaceAction;
use Capell\PublishingStudio\Actions\SaveRecordDraftAction;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\WorkspaceContext;
use Capell\PublishingStudio\WorkspaceRegistry;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TableSelect;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User as AuthenticatedUser;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use RuntimeException;
use Throwable;

final class LayoutBuilderActionFactory
{
    private const string CLEAR_CACHED_URLS_FOR_MODEL_ACTION = ClearCachedUrlsForModelAction::class;

    private const string CREATE_RECORD_DRAFT_WORKSPACE_ACTION = CreateRecordDraftWorkspaceAction::class;

    private const string SAVE_RECORD_DRAFT_ACTION = SaveRecordDraftAction::class;

    private const string WORKSPACE_CLASS = Workspace::class;

    private const string WORKSPACE_CONTEXT_CLASS = WorkspaceContext::class;

    private const string WORKSPACE_REGISTRY_CLASS = WorkspaceRegistry::class;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $pendingLiveDraftableAssetSnapshot = null;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $pendingLiveDraftableRelationSnapshot = null;

    private ?Model $pendingLiveDraftableWorkspace = null;

    public function __construct(private LayoutBuilder $livewire) {}

    public function saveLayoutAction(): Action
    {
        return Action::make('saveLayout')
            ->label(__('capell-layout-builder::button.save_layout'))
            ->tooltip(__('capell-layout-builder::button.save_layout_tooltip'))
            ->color('primary')
            ->size(Size::Small)
            ->button()
            ->action(function (Action $action, LayoutBuilder $livewire): void {
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
            ->visible(fn (): bool => $this->livewire->inPageContext())
            ->action(function (Action $action, LayoutBuilder $livewire): void {
                $this->duplicateLayout();

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
            ->visible(fn (): bool => $this->livewire->layoutIsSharedWithOtherPages())
            ->action(function (Action $action, LayoutBuilder $livewire): void {
                $this->duplicateLayout();

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
            ->tooltip(__('capell-layout-builder::button.undo_tooltip'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->size(Size::Small)
            ->link()
            ->visible(fn (): bool => $this->livewire->layoutUndoSnapshots !== [])
            ->action(fn (): null => $this->livewire->undoLayoutMutation());
    }

    public function redoLayoutMutationAction(): Action
    {
        return Action::make('redoLayoutMutation')
            ->label(__('capell-layout-builder::button.redo'))
            ->tooltip(__('capell-layout-builder::button.redo_tooltip'))
            ->icon('heroicon-o-arrow-uturn-right')
            ->color('gray')
            ->size(Size::Small)
            ->link()
            ->visible(fn (): bool => $this->livewire->layoutRedoSnapshots !== [])
            ->action(fn (): null => $this->livewire->redoLayoutMutation());
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
            ->record(fn (): Layout => $this->livewire->layout)
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitActionLabel(fn (Action $action): string => $this->labelText($action->getTooltip()))
            ->schema(
                static fn (LayoutBuilder $livewire, Schema $schema, array $arguments): Schema => $schema->operation('createOption')
                    ->schema($livewire->getContainerSchema($schema, $arguments)),
            )
            ->action(function (Action $action, LayoutBuilder $livewire, array $data, array $arguments): void {
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
            ->record(fn (): Layout => $this->livewire->layout)
            ->modalWidth(Width::ScreenLarge)
            ->modalHeading(
                fn (array $arguments): string|array => __(
                    'capell-layout-builder::heading.edit_container',
                    ['key' => (string) str($arguments['containerKey'])->title()],
                ),
            )
            ->modalSubmitActionLabel(fn (Action $action): string => $this->labelText($action->getLabel()))
            ->schema(
                static fn (LayoutBuilder $livewire, Schema $schema, array $arguments): Schema => $schema->operation('editOption')
                    ->schema($livewire->getContainerSchema($schema, $arguments)),
            )
            ->fillForm(fn (LayoutBuilder $livewire, array $arguments): array => [
                'key' => $arguments['containerKey'],
                'meta' => $livewire->containers[$arguments['containerKey']]['meta'] ?? [],
            ])
            ->action(function (Action $action, LayoutBuilder $livewire, array $data, array $arguments): void {
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
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments): void {
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
            ->visible(fn (array $arguments, LayoutBuilder $livewire): bool => $livewire->canMoveContainerUp($arguments['containerKey']))
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments): void {
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
            ->visible(fn (array $arguments, LayoutBuilder $livewire): bool => $livewire->canMoveContainerDown($arguments['containerKey']))
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments): void {
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
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments): void {
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
                fn (array $arguments, LayoutBuilder $livewire): bool => (bool) $livewire->getContainerWidgetConfigurator(
                    $arguments['containerKey'],
                    $arguments['widgetIndex'],
                ),
            )
            ->modalHeading(__('capell-layout-builder::heading.container_widget_settings'))
            ->modalSubmitActionLabel(fn (Action $action): string => $this->labelText($action->getLabel()))
            ->modalDescription(
                fn (array $arguments, LayoutBuilder $livewire): string => __(
                    'capell-admin::generic.edit_container_widget',
                    [
                        'container' => $arguments['containerKey'],
                        'widget' => $livewire->getContainerWidget($arguments['containerKey'], $arguments['widgetIndex'])->name,
                    ],
                ),
            )
            ->modalWidth(Width::ScreenSmall)
            ->schema(function (array $arguments, LayoutBuilder $livewire, Schema $schema): Schema {
                $adminSchema = AdminSurfaceLookup::configurator(
                    ConfiguratorTypeEnum::Widget->value,
                    $livewire->getContainerWidgetConfigurator($arguments['containerKey'], $arguments['widgetIndex']) ?? DefaultWidgetConfigurator::getKey(),
                );

                $typeSchema = resolve($adminSchema)->make($schema);

                return $schema->operation('editOption')->components($typeSchema);
            })
            ->fillForm(
                fn (LayoutBuilder $livewire, array $arguments): array => $livewire->containers[$arguments['containerKey']]['widgets'][$arguments['widgetIndex']]['meta'] ?? [],
            )
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments, array $data): void {
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
            ->visible(fn (): bool => (bool) $this->livewire->containers)
            ->modalWidth(Width::ScreenLarge)
            ->extraModalWindowAttributes([
                'class' => 'capell-layout-builder-builder-assets-table',
            ])
            ->closeModalByClickingAway(false)
            ->schema(function (Schema $schema, array $arguments, LayoutBuilder $livewire): Schema {
                $containerOptions = $livewire->getContainerOptions();
                $containerKey = $arguments['containerKey'] ?? null;

                $components = [];

                if (! $containerKey && $containerOptions->count() > 1) {
                    $components[] = Select::make('container')
                        ->label(__('capell-admin::form.container'))
                        ->hiddenLabel()
                        ->prefix(fn (Select $component): string => $this->labelText($component->getLabel()) . ': ')
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
            ->action(function (array $data, array $arguments, LayoutBuilder $livewire): void {
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
                fn (array $arguments): Widget => $this->livewire->getContainerWidget(
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
            ->modalSubmitActionLabel(
                fn (array $arguments): string => __(
                    $this->isDraftableAssetType($arguments)
                        ? 'capell-layout-builder::button.save_asset_draft'
                        : 'capell-layout-builder::button.save_changes',
                ),
            )
            ->successNotificationTitle(__('capell-layout-builder::message.widget_updated'))
            ->fillForm(fn (Widget $record): array => $record->attributesToArray())
            ->schema(
                fn (Action $action, Schema $schema): Schema => WidgetForm::configure(
                    $schema->operation('editOption')
                        ->record(function () use ($action): Widget {
                            $record = $action->getRecord();

                            throw_unless($record instanceof Widget, RuntimeException::class, 'Widget edit action record must be a widget model.');

                            $widget = $record->fresh();

                            throw_unless($widget instanceof Widget, RuntimeException::class, 'Widget edit action record must refresh to a widget model.');

                            return $widget;
                        }),
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
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments): void {
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
            ->visible(fn (array $arguments, LayoutBuilder $livewire): bool => $livewire->canMoveWidgetUp(
                $arguments['containerKey'],
                $arguments['widgetIndex'],
            ))
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments): void {
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
            ->visible(fn (array $arguments, LayoutBuilder $livewire): bool => $livewire->canMoveWidgetDown(
                $arguments['containerKey'],
                $arguments['widgetIndex'],
            ))
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments): void {
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
            ->visible(fn (array $arguments, LayoutBuilder $livewire): bool => $livewire->canMoveWidgetToAnotherContainer(
                $arguments['containerKey'],
                $arguments['widgetIndex'],
            ))
            ->schema(fn (LayoutBuilder $livewire, array $arguments, Schema $schema): Schema => $schema->schema([
                Select::make('target_container')
                    ->label(__('capell-admin::form.container'))
                    ->options(
                        $livewire->getContainerOptions()
                            ->reject(fn (string $label, string $containerKey): bool => $containerKey === $arguments['containerKey'])
                            ->all(),
                    )
                    ->required(),
            ]))
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments, array $data): void {
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
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments): void {
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
            ->modalHeading(function (LayoutBuilder $livewire, array $arguments): string {
                $totalAssets = $livewire->countWidgetAssets($arguments['containerKey'], $arguments['widgetIndex']);

                if ($totalAssets !== 0) {
                    $hasPageAssets = $livewire->hasPageAssets($arguments['containerKey'], $arguments['widgetIndex']);
                } else {
                    $hasPageAssets = $livewire->inPageContext();
                }

                return $hasPageAssets
                    ? __('capell-layout-builder::generic.select_page_widget_asset_description', ['type' => $arguments['type']])
                    : __('capell-layout-builder::generic.select_widget_asset_description', ['type' => $arguments['type']]);
            })
            ->closeModalByClickingAway(false)
            ->schema(function (Schema $schema, array $arguments, LayoutBuilder $livewire): Schema {
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
            ->action(function (array $data, array $arguments, LayoutBuilder $livewire): void {
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
                fn (array $arguments, LayoutBuilder $livewire): string => __(
                    'capell-layout-builder::generic.add_widget_asset',
                    [
                        'widget' => $livewire->getContainerWidget($arguments['containerKey'], $arguments['widgetIndex'])->name,
                        'asset' => $arguments['type'],
                    ],
                ),
            )
            ->modalSubmitActionLabel(
                fn (array $arguments, Action $action): string => __(
                    $this->isDraftableAssetType($arguments)
                        ? 'capell-layout-builder::button.create_asset_draft'
                        : 'capell-layout-builder::button.create_widget_asset',
                    ['type' => $arguments['type']],
                ),
            )
            ->successNotificationTitle(__('capell-layout-builder::message.asset_added'))
            ->schema(
                fn (array $arguments, Schema $schema): Schema => $this->getWidgetAssetSchema(
                    $schema->operation('createOption')
                        ->record(fn (): WidgetAsset => $this->makeWidgetAssetRecordForCreate($arguments)),
                ),
            )
            ->model(fn (): string => WidgetAsset::class)
            ->fillForm(function (array $arguments): array {
                $containerKey = $arguments['containerKey'];
                $widgetIndex = $arguments['widgetIndex'];
                $assetType = $arguments['type'];

                $widget = $this->livewire->getContainerWidget($containerKey, $widgetIndex);

                $asset = CapellAdmin::getAsset($assetType);

                return [
                    'widget_id' => $widget->id,
                    'workspace_id' => $this->livewire->getCurrentWidgetAssetWorkspaceId($widget),
                    'asset_type' => $assetType,
                    'meta' => [],
                    'asset' => in_array($asset->defaultDataAction, [null, '', '0'], true)
                        ? []
                        : $asset->defaultDataAction::run(),
                ];
            })
            ->action($this->addAssetFromAction(...));
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
            ->visible(fn (LayoutBuilder $livewire): bool => $livewire->canEditContent())
            ->icon(fn (array $arguments): string|BackedEnum => CapellCore::getAsset($arguments['type'])->getIcon() ?? 'heroicon-o-pencil-square')
            ->iconSize(IconSize::Small)
            ->tooltip(
                fn (array $arguments): string => __(
                    'capell-layout-builder::button.edit_asset_type',
                    ['type' => $arguments['type']],
                ),
            )
            ->modalWidth(Width::ScreenLarge)
            ->modalHeading(
                fn (LayoutBuilder $livewire, array $arguments): string => $this->getEditWidgetAssetModalHeading($livewire, $arguments),
            )
            ->modalDescription(
                fn (LayoutBuilder $livewire, array $arguments): ?string => $this->getEditWidgetAssetModalDescription($livewire, $arguments),
            )
            ->modalSubmitActionLabel(
                fn (array $arguments): string => __(
                    $this->isDraftableAssetType($arguments)
                        ? 'capell-layout-builder::button.save_asset_draft'
                        : 'capell-layout-builder::button.save_changes',
                ),
            )
            ->successNotificationTitle(__('capell-layout-builder::message.asset_updated'))
            ->schema(
                fn (LayoutBuilder $livewire, Schema $schema, array $arguments): Schema => $this->getWidgetAssetSchema(
                    $schema->operation('editOption')
                        ->record(fn (): WidgetAsset => $this->resolveEditableWidgetAsset($arguments)),
                ),
            )
            ->fillForm(fn (WidgetAsset $record, array $arguments): array => [
                'meta' => $record->meta,
                'asset' => $this->editableAssetFormState($record),
            ])
            ->record(fn (array $arguments): WidgetAsset => $this->resolveEditableWidgetAsset($arguments))
            ->disabled(fn (WidgetAsset $record): bool => ! $record->exists)
            ->action(
                fn (WidgetAsset $record, array $data, LayoutBuilder $livewire, array $arguments, Action $action, Schema $schema) => $this->applyWidgetAssetUpdate(
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
            ->visible(fn (array $arguments, LayoutBuilder $livewire): bool => $livewire->canMoveAssetUp(
                $arguments['containerKey'],
                (int) $arguments['widgetIndex'],
                (int) $arguments['assetIndex'],
            ))
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments): void {
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
            ->visible(fn (array $arguments, LayoutBuilder $livewire): bool => $livewire->canMoveAssetDown(
                $arguments['containerKey'],
                (int) $arguments['widgetIndex'],
                (int) $arguments['assetIndex'],
            ))
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments): void {
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
            ->action(function (LayoutBuilder $livewire, array $arguments, Action $action): void {
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
            ->visible(fn (): bool => $this->livewire->inPageContext())
            ->schema(
                fn (Schema $schema, LayoutBuilder $livewire): Schema => $schema->operation('editOption')
                    ->schema($this->getChangeLayoutSchema()),
            )
            ->fillForm(fn (LayoutBuilder $livewire): array => ['layout_id' => $livewire->layout->getKey()])
            ->modalSubmitActionLabel(__('capell-layout-builder::button.change_layout'))
            ->action(function (LayoutBuilder $livewire, Action $action, array $data): void {
                $this->changePageLayout($data['layout_id']);

                $this->livewire->dispatch('page-layout-changed', id: $data['layout_id']);

                $action->success();
            });
    }

    public function togglePageAssetsAction(): Action
    {
        return Action::make('togglePageAssets')
            ->label(
                function (LayoutBuilder $livewire, array $arguments): string {
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
            ->visible(function (LayoutBuilder $livewire, array $arguments): bool {
                if (! $livewire->inPageContext()) {
                    return false;
                }

                $this->livewire->ensureLoaded();

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
                function (LayoutBuilder $livewire, array $arguments): string {
                    $hasPageAssets = $livewire->hasPageAssets(
                        containerKey: $arguments['containerKey'],
                        widgetIndex: $arguments['widgetIndex'],
                    );

                    return $hasPageAssets
                        ? __('capell-layout-builder::generic.convert_widget_assets')
                        : __('capell-admin::generic.convert_page_assets');
                },
            )
            ->action(function (LayoutBuilder $livewire, array $arguments, Action $action): void {
                $this->livewire->ensureLoaded();

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

    private function labelText(Htmlable|string|null $label): string
    {
        return $label instanceof Htmlable ? $label->toHtml() : (string) $label;
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     * @param  array<array-key, mixed>  $data
     */
    private function addAssetFromAction(Action $action, array $arguments, array $data): void
    {
        $this->livewire->assertCanUpdateLayout();

        $this->livewire->loadFromStore();

        $configurator = $this->livewire->getLayoutBuilderMountedActionSchema();

        throw_unless($configurator instanceof Schema, Exception::class, 'Mounted action schema not found.');

        $configurator->livewire($this->livewire);

        $containerKey = $arguments['containerKey'];
        $widgetIndex = $arguments['widgetIndex'];
        $type = $arguments['type'];

        $hasPageAssets = $this->livewire->shouldAddPageAssets($containerKey, $widgetIndex);

        $widget = $this->livewire->getContainerWidget($containerKey, $widgetIndex);

        $order = $this->livewire->countWidgetAssets($containerKey, $widgetIndex) + 1;

        /** @var WidgetAsset $widgetAsset */
        $widgetAsset = $configurator->getRecord();

        // Fake exists to ensure assets relations are saved correctly
        $widgetAsset->exists = true;
        $widgetAsset->wasRecentlyCreated = true; // prevent MissingAttributeException

        $data['widget_id'] = $widget->id;
        $draftableNewAssetWorkspace = $this->workspaceForNewDraftableAsset($widgetAsset, $type);
        $draftableNewAsset = $this->createDraftableAssetFromBuilderData($type, $data, $draftableNewAssetWorkspace);

        if ($draftableNewAsset instanceof Model) {
            $newAssetKey = $draftableNewAsset->getKey();
            $widgetAsset->asset_id = is_scalar($newAssetKey) ? (string) $newAssetKey : '';
            $widgetAsset->setRelation('asset', $draftableNewAsset);
        }

        // Ensure UpdatedModelAction is not triggered
        WidgetAsset::withoutEvents(function () use ($widgetAsset, $configurator, $draftableNewAssetWorkspace): void {
            if ($this->isWorkspace($draftableNewAssetWorkspace)) {
                $workspaceContextClass = self::WORKSPACE_CONTEXT_CLASS;
                $workspaceContextClass::runWith($draftableNewAssetWorkspace, function () use ($widgetAsset, $configurator, $draftableNewAssetWorkspace): void {
                    $configurator->saveRelationships();
                    $this->moveCreatedDraftableAssetIntoWorkspace($widgetAsset, $draftableNewAssetWorkspace);
                });

                return;
            }

            $configurator->saveRelationships();
        });

        if ($this->isWorkspace($draftableNewAssetWorkspace) && ! $this->isWorkspace($this->currentWorkspace())) {
            Notification::make('created-asset-draft')
                ->title(__('capell-layout-builder::message.asset_draft_saved', ['workspace' => (string) $draftableNewAssetWorkspace->getAttribute('name')]))
                ->success()
                ->send();

            $this->livewire->dispatch('workspace-changed', workspaceId: $draftableNewAssetWorkspace->id);

            $action->success();
            $this->notifyFrontendAuthoringSaved('pending_approval');

            return;
        }

        if (! isset($this->livewire->assets[$containerKey][$widgetIndex])) {
            $this->livewire->assets[$containerKey][$widgetIndex] = [];
        }

        $assetId = $widgetAsset->asset_id;

        $widget = $this->livewire->getContainerWidget($containerKey, $widgetIndex);

        $occurrence = $this->livewire->getContainerWidgetOccurrence($containerKey, $widgetIndex);

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
            $asset['pageable_id'] = $this->livewire->pageContext()->getKey();
            $asset['pageable_type'] = $this->livewire->pageContext()->getMorphClass();
            $asset['container'] = $containerKey;
        }

        $this->livewire->assets[$containerKey][$widgetIndex][] = $asset;

        $widgetAsset->load([
            'asset' => fn (MorphTo $query): MorphTo => $query->morphWith($this->livewire->getAssetRelations()),
        ]);

        $widgetAsset->setRelation('widget', $widget);

        $widget->assets->add($widgetAsset);

        $this->livewire->layoutUpdated();

        $action->success();

        $this->livewire->dispatch(
            'refresh-assets',
            containerKey: $containerKey,
            widgetIndex: $widgetIndex,
        );
    }

    private function moveCreatedDraftableAssetIntoWorkspace(WidgetAsset $widgetAsset, Model $workspace): void
    {
        $asset = $widgetAsset->getRelationValue('asset');

        if (! $asset instanceof Model || ! $asset->exists) {
            return;
        }

        if (! DB::getSchemaBuilder()->hasColumn($asset->getTable(), 'workspace_id')) {
            return;
        }

        DB::table($asset->getTable())
            ->where($asset->getKeyName(), $asset->getKey())
            ->update(['workspace_id' => $workspace->id]);

        $asset->setAttribute('workspace_id', $workspace->id);
    }

    private function workspaceForNewDraftableAsset(WidgetAsset $widgetAsset, string $type): ?Model
    {
        $createWorkspaceAction = self::CREATE_RECORD_DRAFT_WORKSPACE_ACTION;
        $workspaceRegistry = self::WORKSPACE_REGISTRY_CLASS;

        if (! class_exists($createWorkspaceAction) || ! class_exists($workspaceRegistry)) {
            return null;
        }

        $activeWorkspace = $this->currentWorkspace();

        if ($this->isWorkspace($activeWorkspace)) {
            return $activeWorkspace;
        }

        try {
            $asset = CapellCore::getAsset($type);
        } catch (Throwable) {
            return null;
        }

        $modelClass = $asset->model ?? null;

        if (! is_string($modelClass) || ! is_a($modelClass, Model::class, true) || ! $workspaceRegistry::isRegistered($modelClass)) {
            return null;
        }

        $user = auth()->user();

        if (! $user instanceof AuthenticatedUser) {
            return null;
        }

        $record = $widgetAsset->getRelationValue('asset');

        if (! $record instanceof Model) {
            $record = new $modelClass;
        }

        $workspace = $createWorkspaceAction::run($record, $user);

        return $this->isWorkspace($workspace) ? $workspace : null;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function createDraftableAssetFromBuilderData(string $type, array $data, ?Model $workspace): ?Model
    {
        $workspaceRegistry = self::WORKSPACE_REGISTRY_CLASS;

        if (! $this->isWorkspace($workspace) || ! class_exists($workspaceRegistry)) {
            return null;
        }

        try {
            $asset = CapellCore::getAsset($type);
        } catch (Throwable) {
            return null;
        }

        $modelClass = $asset->model ?? null;

        if (! is_string($modelClass) || ! is_a($modelClass, Model::class, true) || ! $workspaceRegistry::isRegistered($modelClass)) {
            return null;
        }

        $assetData = $data['asset'] ?? [];

        if (! is_array($assetData)) {
            $assetData = [];
        }

        $record = new $modelClass;
        $record->setAttribute('workspace_id', $workspace->id);
        $record->fill($assetData);
        $record->save();

        return $record;
    }

    private function duplicateLayout(): void
    {
        $this->livewire->assertCanUpdateLayout();

        $newLayout = ReplicateLayoutAction::run($this->livewire->layout);

        $this->livewire->dispatch('page-layout-changed', id: $newLayout->getKey());
    }

    private function changePageLayout(int $layoutId): void
    {
        $this->livewire->assertCanUpdateLayout();

        if (! $this->livewire->inPageContext()) {
            return;
        }

        $this->livewire->layoutUpdated();

        $this->livewire->dispatch('page-layout-changed', id: $layoutId);
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     */
    private function makeWidgetAssetRecordForCreate(array $arguments): WidgetAsset
    {
        $containerKey = $arguments['containerKey'];
        $widgetIndex = $arguments['widgetIndex'];
        $assetType = $arguments['type'];

        $widget = $this->livewire->getContainerWidget($containerKey, $widgetIndex);

        /** @var class-string<WidgetAsset> $model */
        $model = WidgetAsset::class;

        $record = $model::query()->make([
            'widget_id' => $widget->id,
            'workspace_id' => $this->livewire->getCurrentWidgetAssetWorkspaceId($widget),
            'asset_type' => $assetType,
            'meta' => [],
        ]);

        $asset = CapellCore::getAsset($assetType)->model::make();

        $record->setRelation('asset', $asset);

        return $record;
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     */
    private function resolveEditableWidgetAsset(array $arguments): WidgetAsset
    {
        $containerKey = $arguments['containerKey'];
        $widgetIndex = $arguments['widgetIndex'];
        $index = $arguments['index'];
        $type = $arguments['type'];

        $widget = $this->livewire->getContainerWidget($containerKey, $widgetIndex);
        $asset = $this->livewire->getWidgetAsset($containerKey, $widgetIndex, $index);

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
        throw_unless((int) $widgetAsset->getAttribute('widget_id') === (int) $widget->getKey(), Exception::class, sprintf('Asset of type [%s] with ID [%s] is not attached to this widget.', $type, $assetId));

        return $widgetAsset;
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     */
    private function getEditWidgetAssetModalHeading(LayoutBuilder $livewire, array $arguments): string
    {
        $name = str($arguments['type'])->title();

        if ($livewire->inPageContext()) {
            return __('capell-layout-builder::heading.edit_page_widget_asset', ['name' => (string) $name]);
        }

        return __('capell-layout-builder::heading.edit_widget_asset', ['name' => (string) $name]);
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     */
    private function getEditWidgetAssetModalDescription(LayoutBuilder $livewire, array $arguments): ?string
    {
        if (! $livewire->inPageContext()) {
            return null;
        }

        $widgetAsset = $this->livewire->getWidgetAsset($arguments['containerKey'], $arguments['widgetIndex'], $arguments['index']);

        if (! isset($widgetAsset['pageable_id'], $widgetAsset['pageable_type'])) {
            return null;
        }

        return __('capell-layout-builder::heading.page_widget_asset', ['name' => $livewire->pageContext()->name]);
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     * @param  array<array-key, mixed>  $data
     */
    private function applyWidgetAssetUpdate(WidgetAsset $record, array $data, LayoutBuilder $livewire, array $arguments, Action $action, Schema $configurator): void
    {
        $this->livewire->assertCanEditContent();

        $this->livewire->loadFromStore();

        $expectedSignature = $arguments['contentInventorySignature'] ?? null;

        if (is_string($expectedSignature) && ! hash_equals($expectedSignature, $this->livewire->contentInventorySignature())) {
            Notification::make('content-inventory-stale')
                ->title(__('capell-layout-builder::message.content_stale'))
                ->warning()
                ->send();

            $action->halt();
        }

        $widget = $this->livewire->getContainerWidget($arguments['containerKey'], $arguments['widgetIndex']);
        $canUpdatePersistedRecord = $record->workspace_id === $this->livewire->getCurrentWidgetAssetWorkspaceId($widget);
        $assetDrafted = $this->saveDraftableAssetFromWidgetAsset($record, $data, $canUpdatePersistedRecord);

        if ($canUpdatePersistedRecord && ! $assetDrafted) {
            $configurator->saveRelationships();
        }

        if ($data !== [] && $canUpdatePersistedRecord && ! $assetDrafted) {
            $record->update($data);
        }

        if (! $canUpdatePersistedRecord && ! $assetDrafted) {
            Notification::make('content-asset-read-only')
                ->title(__('capell-layout-builder::message.asset_not_saved'))
                ->warning()
                ->send();

            $action->halt();
        }

        if (isset($data['meta'])) {
            $livewire->updateWidgetAssetContentState($arguments['containerKey'], $arguments['widgetIndex'], $arguments['index'], ['meta' => $data['meta']]);
        }

        $livewire->reloadContainerWidgetAsset($arguments['containerKey'], $arguments['widgetIndex'], $arguments['index']);
        $this->restorePendingLiveDraftableAssetSnapshot();
        $livewire->layoutUpdated();

        $action->success();
        $this->notifyFrontendAuthoringSaved();
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function saveDraftableAssetFromWidgetAsset(WidgetAsset $record, array $data, bool $canUpdatePersistedRecord): bool
    {
        $saveRecordDraftAction = self::SAVE_RECORD_DRAFT_ACTION;
        $workspaceRegistry = self::WORKSPACE_REGISTRY_CLASS;

        if (! class_exists($saveRecordDraftAction) || ! class_exists($workspaceRegistry)) {
            return false;
        }

        $asset = $record->asset;

        if (! $asset instanceof Model || ! $workspaceRegistry::isRegistered($asset::class)) {
            return false;
        }

        if (! array_key_exists('workspace_id', $asset->getAttributes())) {
            return false;
        }

        $user = auth()->user();

        if (! $user instanceof AuthenticatedUser) {
            return false;
        }

        $assetData = $data['asset'] ?? $data;

        if (! is_array($assetData)) {
            $assetData = $data;
        }

        $workspace = $this->currentWorkspace();
        $liveSnapshot = (int) $asset->getAttribute('workspace_id') === 0
            ? $asset->getRawOriginal()
            : null;
        $liveRelationSnapshot = $liveSnapshot !== null
            ? $this->liveDraftableAssetRelationSnapshot($asset)
            : null;
        $this->pendingLiveDraftableAssetSnapshot = $liveSnapshot === null
            ? null
            : ['__class' => $asset::class, '__key_name' => $asset->getKeyName(), ...$liveSnapshot];
        $this->pendingLiveDraftableRelationSnapshot = $liveRelationSnapshot;

        $result = $saveRecordDraftAction::run(
            record: $asset,
            data: $assetData,
            user: $user,
            workspace: $this->isWorkspace($workspace) ? $workspace : null,
            saveRelationships: fn (Model $draft): null => $this->saveDraftableAssetRelationData($draft, $assetData),
        );

        $resultWorkspace = $result->workspace ?? null;
        $resultRecord = $result->record ?? null;

        if (! $this->isWorkspace($resultWorkspace) || ! $resultRecord instanceof Model) {
            return false;
        }

        if ($liveSnapshot !== null) {
            $this->restoreLiveDraftableAssetSnapshot($asset, $liveSnapshot, $resultWorkspace);
            $this->pendingLiveDraftableWorkspace = $resultWorkspace;
        }

        if ($liveRelationSnapshot !== null) {
            $this->restoreLiveDraftableAssetRelationSnapshot($asset, $liveRelationSnapshot);
        }

        if ($canUpdatePersistedRecord && (int) $record->workspace_id > 0) {
            $resultRecordKey = $resultRecord->getKey();
            $record->asset_id = is_scalar($resultRecordKey) ? (string) $resultRecordKey : '';
            $record->save();
        }

        if (! $this->isWorkspace($this->currentWorkspace())) {
            $this->livewire->dispatch('workspace-changed', workspaceId: $resultWorkspace->id);
        }

        Notification::make('saved-asset-draft')
            ->title(__('capell-layout-builder::message.asset_draft_saved', ['workspace' => (string) $resultWorkspace->getAttribute('name')]))
            ->success()
            ->send();

        return true;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function restoreLiveDraftableAssetSnapshot(Model $asset, array $snapshot, Model $workspace): void
    {
        $keyName = $asset->getKeyName();
        $payload = Arr::except($snapshot, [$keyName]);
        $payload['shadowed_by_workspace_id'] = $workspace->id;

        DB::table($asset->getTable())
            ->where($keyName, $asset->getKey())
            ->where('workspace_id', 0)
            ->update($payload);
    }

    private function restorePendingLiveDraftableAssetSnapshot(): void
    {
        if ($this->pendingLiveDraftableAssetSnapshot === null || ! $this->isWorkspace($this->pendingLiveDraftableWorkspace)) {
            return;
        }

        $assetClass = $this->pendingLiveDraftableAssetSnapshot['__class'] ?? null;
        $keyName = $this->pendingLiveDraftableAssetSnapshot['__key_name'] ?? null;

        if (! is_string($assetClass) || ! is_a($assetClass, Model::class, true) || ! is_string($keyName)) {
            return;
        }

        $assetId = $this->pendingLiveDraftableAssetSnapshot[$keyName] ?? null;

        if (! is_numeric($assetId)) {
            return;
        }

        $asset = new $assetClass;
        $asset->setAttribute($keyName, (int) $assetId);

        $this->restoreLiveDraftableAssetSnapshot(
            asset: $asset,
            snapshot: Arr::except($this->pendingLiveDraftableAssetSnapshot, ['__class', '__key_name']),
            workspace: $this->pendingLiveDraftableWorkspace,
        );

        if ($this->pendingLiveDraftableRelationSnapshot !== null) {
            $this->restoreLiveDraftableAssetRelationSnapshot($asset, $this->pendingLiveDraftableRelationSnapshot);
        }

        $this->pendingLiveDraftableAssetSnapshot = null;
        $this->pendingLiveDraftableRelationSnapshot = null;
        $this->pendingLiveDraftableWorkspace = null;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function liveDraftableAssetRelationSnapshot(Model $asset): ?array
    {
        if (! method_exists($asset, 'translations')) {
            return null;
        }

        return $asset->translations()
            ->get()
            ->map(static fn (Model $translation): array => $translation->getRawOriginal())
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $snapshot
     */
    private function restoreLiveDraftableAssetRelationSnapshot(Model $asset, array $snapshot): void
    {
        if (! method_exists($asset, 'translations')) {
            return;
        }

        $related = $asset->translations()->getRelated();
        $keyName = $related->getKeyName();

        foreach ($snapshot as $row) {
            $key = $row[$keyName] ?? null;

            if (! is_numeric($key)) {
                continue;
            }

            DB::table($related->getTable())
                ->where($keyName, $key)
                ->update(Arr::except($row, [$keyName]));
        }
    }

    /**
     * @param  array<array-key, mixed>  $assetData
     */
    private function saveDraftableAssetRelationData(Model $draft, array $assetData): null
    {
        $translations = $assetData['translations'] ?? null;

        if (! is_array($translations) || ! method_exists($draft, 'translations')) {
            return null;
        }

        $relation = $draft->translations();
        $related = $relation->getRelated();
        $fillable = $related->getFillable();

        foreach ($translations as $translationData) {
            if (! is_array($translationData)) {
                continue;
            }

            $languageId = $translationData['language_id'] ?? null;

            if (! is_numeric($languageId)) {
                continue;
            }

            $payload = array_intersect_key($translationData, array_flip($fillable));
            $payload['language_id'] = (int) $languageId;

            $relation->updateOrCreate(
                ['language_id' => (int) $languageId],
                $payload,
            );
        }

        return null;
    }

    private function getWidgetAssetSchema(Schema $configurator): Schema
    {
        return WidgetAssetForm::configure($configurator);
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     */
    private function isDraftableAssetType(array $arguments): bool
    {
        $workspaceRegistry = self::WORKSPACE_REGISTRY_CLASS;

        if (! class_exists($workspaceRegistry)) {
            return false;
        }

        $type = $arguments['type'] ?? null;

        if (! is_string($type) || $type === '') {
            return false;
        }

        try {
            $asset = CapellCore::getAsset($type);
        } catch (Throwable) {
            return false;
        }

        $modelClass = $asset->model ?? null;

        return is_string($modelClass) && is_a($modelClass, Model::class, true) && $workspaceRegistry::isRegistered($modelClass);
    }

    /**
     * @return array<string, mixed>
     */
    private function editableAssetFormState(WidgetAsset $record): array
    {
        $record->loadMissing('asset');

        $asset = $record->asset;

        return $asset instanceof Model ? $asset->attributesToArray() : [];
    }

    /**
     * @return array<array-key, mixed>
     */
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
                            $this->livewire->getSite(),
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
                                $this->livewire->getSite(),
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

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function saveWidgetForm(Schema $configurator, Widget $record, array $data): void
    {
        $this->livewire->assertCanUpdateLayout();

        $this->livewire->ensureLoaded();

        $configurator->saveRelationships();

        $record->update($data);

        $this->clearCachedPagesForWidget($record);
        $this->notifyFrontendAuthoringSaved();
    }

    private function clearCachedPagesForWidget(Widget $record): void
    {
        $actionClass = self::CLEAR_CACHED_URLS_FOR_MODEL_ACTION;

        if (! class_exists($actionClass)) {
            return;
        }

        $actionClass::run(
            $record,
            refresh: config('capell-admin.auto_refresh_cache') === true,
        );
    }

    private function currentWorkspace(): ?Model
    {
        $workspaceContextClass = self::WORKSPACE_CONTEXT_CLASS;

        if (! class_exists($workspaceContextClass)) {
            return null;
        }

        $workspace = $workspaceContextClass::current();

        return $workspace instanceof Model ? $workspace : null;
    }

    private function isWorkspace(mixed $workspace): bool
    {
        return $workspace instanceof Model
            && class_exists(self::WORKSPACE_CLASS)
            && $workspace instanceof Workspace;
    }

    private function notifyFrontendAuthoringSaved(string $status = 'published'): void
    {
        $this->livewire->dispatch('capell-layout-builder-authoring-saved', status: $status, redirectUrl: null);
    }
}
