<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Actions;

use BackedEnum;
use Capell\Admin\Actions\ReplicateLayoutAction;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Exceptions\MissingBlockAssetException;
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
    /**
     * @var array<string, mixed>|null
     */
    private ?array $pendingLiveDraftableAssetSnapshot = null;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $pendingLiveDraftableRelationSnapshot = null;

    private ?Workspace $pendingLiveDraftableWorkspace = null;

    public function __construct(private LayoutBuilder $livewire) {}

    public function saveLayoutAction(): Action
    {
        return Action::make('saveLayout')
            ->label(__('capell-layout-builder::button.save_layout'))
            ->color('primary')
            ->size(Size::Small)
            ->button()
            ->outlined()
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
            ->modalSubmitActionLabel(fn (Action $action): string => $action->getTooltip())
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
            ->modalSubmitActionLabel(fn (Action $action): string => $action->getLabel())
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

    public function editLayoutBlockAction(): Action
    {
        return Action::make('editLayoutBlock')
            ->label(__('capell-layout-builder::button.edit_layout_block'))
            ->groupedIcon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->grouped()
            ->visible(
                fn (array $arguments, LayoutBuilder $livewire): bool => (bool) $livewire->getContainerBlockConfigurator(
                    $arguments['containerKey'],
                    $arguments['blockIndex'],
                ),
            )
            ->modalHeading(__('capell-layout-builder::heading.container_block_settings'))
            ->modalSubmitActionLabel(fn (Action $action): string => $action->getLabel())
            ->modalDescription(
                fn (array $arguments, LayoutBuilder $livewire): string => __(
                    'capell-admin::generic.edit_container_block',
                    [
                        'container' => $arguments['containerKey'],
                        'block' => $livewire->getContainerBlock($arguments['containerKey'], $arguments['blockIndex'])->name,
                    ],
                ),
            )
            ->modalWidth(Width::ScreenSmall)
            ->schema(function (array $arguments, LayoutBuilder $livewire, Schema $schema): Schema {
                $adminSchema = AdminSurfaceLookup::configurator(
                    ConfiguratorTypeEnum::LayoutBlock->value,
                    $livewire->getContainerBlockConfigurator($arguments['containerKey'], $arguments['blockIndex']),
                );

                $typeSchema = resolve($adminSchema)->make($schema);

                return $schema->operation('editOption')->components($typeSchema);
            })
            ->fillForm(
                fn (LayoutBuilder $livewire, array $arguments): array => $livewire->containers[$arguments['containerKey']]['widgets'][$arguments['blockIndex']]['meta'] ?? [],
            )
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments, array $data): void {
                $livewire->editLayoutBlock($arguments['containerKey'], $arguments['blockIndex'], $data);

                $action->success();
            });
    }

    public function addBlockAction(): Action
    {
        return Action::make('addBlock')
            ->label(fn (array $arguments): string => isset($arguments['position'])
                ? __('capell-layout-builder::button.add_block_here')
                : __('capell-layout-builder::button.add_block'))
            ->tooltip(__('capell-layout-builder::button.add_block'))
            ->modalHeading(__('capell-layout-builder::heading.add_block_to_container'))
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
                        ->prefix(fn (Select $component): string => $component->getLabel() . ': ')
                        ->required()
                        ->options($containerOptions);
                }

                $components[] = TableSelect::make('widgets')
                    ->label(__('capell-layout-builder::button.block'))
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

                $livewire->addBlocksToContainer(
                    containerKey: (string) $containerKey,
                    blocks: $data['widgets'] ?? [],
                    position: isset($arguments['position']) ? (int) $arguments['position'] : null,
                );
            });
    }

    public function editBlockAction(): Action
    {
        return Action::make('editBlock')
            ->label(__('capell-layout-builder::button.edit_block'))
            ->tooltip(__('capell-layout-builder::button.edit_block'))
            ->button()
            ->slideOver()
            ->closeModalByClickingAway(false)
            ->icon('heroicon-o-pencil')
            ->color('gray')
            ->size(Size::Small)
            ->modalWidth(Width::ScreenLarge)
            ->record(
                fn (array $arguments): Widget => $this->livewire->getContainerBlock(
                    $arguments['containerKey'],
                    $arguments['blockIndex'],
                ),
            )
            ->modalHeading(fn (Widget $record): string => $record->name)
            ->modalDescription(
                fn (Widget $record): string => __(
                    'capell-layout-builder::heading.block_type',
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
            ->successNotificationTitle(__('capell-layout-builder::message.block_updated'))
            ->fillForm(fn (Widget $record): array => $record->attributesToArray())
            ->schema(
                fn (Action $action, Schema $schema): Schema => WidgetForm::configure(
                    $schema->operation('editOption')
                        ->record(function () use ($action): Widget {
                            $block = $action->getRecord()->fresh();

                            throw_unless($block instanceof Widget, RuntimeException::class, 'Widget edit action record must refresh to a block model.');

                            return $block;
                        }),
                ),
            )
            ->action(function (Action $action, Widget $record, Schema $schema, array $data): void {
                $this->saveWidgetForm(configurator: $schema, record: $record, data: $data);

                $action->success();
            });
    }

    public function duplicateBlockAction(): Action
    {
        return Action::make('duplicateBlock')
            ->label(__('capell-layout-builder::button.duplicate_block'))
            ->grouped()
            ->groupedIcon('heroicon-o-square-2-stack')
            ->color('gray')
            ->size('sm')
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments): void {
                $livewire->duplicateBlock(containerKey: $arguments['containerKey'], originalIndex: $arguments['blockIndex']);

                $action->success();
            });
    }

    public function moveBlockUpAction(): Action
    {
        return Action::make('moveBlockUp')
            ->label(__('capell-layout-builder::button.move_up'))
            ->grouped()
            ->groupedIcon('heroicon-o-arrow-up')
            ->color('gray')
            ->size(Size::Small)
            ->visible(fn (array $arguments, LayoutBuilder $livewire): bool => $livewire->canMoveBlockUp(
                $arguments['containerKey'],
                $arguments['blockIndex'],
            ))
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments): void {
                $livewire->moveBlockUp($arguments['containerKey'], $arguments['blockIndex']);

                $action->success();
            });
    }

    public function moveBlockDownAction(): Action
    {
        return Action::make('moveBlockDown')
            ->label(__('capell-layout-builder::button.move_down'))
            ->grouped()
            ->groupedIcon('heroicon-o-arrow-down')
            ->color('gray')
            ->size(Size::Small)
            ->visible(fn (array $arguments, LayoutBuilder $livewire): bool => $livewire->canMoveBlockDown(
                $arguments['containerKey'],
                $arguments['blockIndex'],
            ))
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments): void {
                $livewire->moveBlockDown($arguments['containerKey'], $arguments['blockIndex']);

                $action->success();
            });
    }

    public function moveBlockToContainerAction(): Action
    {
        return Action::make('moveBlockToContainer')
            ->label(__('capell-layout-builder::button.move_to_container'))
            ->grouped()
            ->groupedIcon('heroicon-o-arrow-right')
            ->color('gray')
            ->size(Size::Small)
            ->modalWidth(Width::ScreenSmall)
            ->modalHeading(__('capell-layout-builder::button.move_to_container'))
            ->visible(fn (array $arguments, LayoutBuilder $livewire): bool => $livewire->canMoveBlockToAnotherContainer(
                $arguments['containerKey'],
                $arguments['blockIndex'],
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
                $livewire->moveBlockToContainer(
                    $arguments['containerKey'],
                    $arguments['blockIndex'],
                    (string) $data['target_container'],
                );

                $action->success();
            });
    }

    public function removeBlockAction(): Action
    {
        return Action::make('removeBlock')
            ->label(__('capell-layout-builder::button.remove_block'))
            ->grouped()
            ->groupedIcon('heroicon-m-trash')
            ->color('danger')
            ->size('sm')
            ->requiresConfirmation()
            ->modalDescription(__('capell-layout-builder::message.remove_block_confirmation'))
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments): void {
                $livewire->removeBlock(containerKey: $arguments['containerKey'], blockIndex: $arguments['blockIndex']);

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
                $totalAssets = $livewire->countBlockAssets($arguments['containerKey'], $arguments['blockIndex']);

                if ($totalAssets !== 0) {
                    $hasPageAssets = $livewire->hasPageAssets($arguments['containerKey'], $arguments['blockIndex']);
                } else {
                    $hasPageAssets = $livewire->inPageContext();
                }

                return $hasPageAssets
                    ? __('capell-admin::generic.select_page_block_asset_description', ['type' => $arguments['type']])
                    : __('capell-admin::generic.select_block_asset_description', ['type' => $arguments['type']]);
            })
            ->closeModalByClickingAway(false)
            ->schema(function (Schema $schema, array $arguments, LayoutBuilder $livewire): Schema {
                $tableConfiguration = PageSelectionTable::class;

                $excludeIds = $livewire->getBlockAssetsByType(
                    $arguments['containerKey'],
                    (int) $arguments['blockIndex'],
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
                $blockIndex = (int) $arguments['blockIndex'];
                $type = $arguments['type'];

                $hasPageAssets = $livewire->countBlockAssets($containerKey, $blockIndex) > 0
                    ? $livewire->hasPageAssets($containerKey, $blockIndex)
                    : $livewire->inPageContext();

                $livewire->addAssetsToBlock(
                    arguments: [
                        'containerKey' => $containerKey,
                        'blockIndex' => $blockIndex,
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
                    'capell-admin::generic.add_block_asset',
                    [
                        'block' => $livewire->getContainerBlock($arguments['containerKey'], $arguments['blockIndex'])->name,
                        'asset' => $arguments['type'],
                    ],
                ),
            )
            ->modalSubmitActionLabel(
                fn (array $arguments, Action $action): string => __(
                    $this->isDraftableAssetType($arguments)
                        ? 'capell-layout-builder::button.create_asset_draft'
                        : 'capell-layout-builder::button.create_block_asset',
                    ['type' => $arguments['type']],
                ),
            )
            ->successNotificationTitle(__('capell-layout-builder::message.asset_added'))
            ->schema(
                fn (array $arguments, Schema $schema): Schema => $this->getBlockAssetSchema(
                    $schema->operation('createOption')
                        ->record(fn (): WidgetAsset => $this->makeBlockAssetRecordForCreate($arguments)),
                ),
            )
            ->model(fn (): string => WidgetAsset::class)
            ->fillForm(function (array $arguments): array {
                $containerKey = $arguments['containerKey'];
                $blockIndex = $arguments['blockIndex'];
                $assetType = $arguments['type'];

                $block = $this->livewire->getContainerBlock($containerKey, $blockIndex);

                $asset = CapellAdmin::getAsset($assetType);

                return [
                    'widget_id' => $block->id,
                    'workspace_id' => $this->livewire->getCurrentBlockAssetWorkspaceId($block),
                    'asset_type' => $assetType,
                    'meta' => [],
                    'asset' => in_array($asset->defaultDataAction, [null, '', '0'], true)
                        ? []
                        : $asset->defaultDataAction::run(),
                ];
            })
            ->action($this->addAssetFromAction(...));
    }

    public function editBlockAssetAction(): Action
    {
        return Action::make('editBlockAsset')
            ->label(__('capell-admin::button.edit'))
            ->button()
            ->modal()
            ->slideOver()
            ->closeModalByClickingAway(false)
            ->color('primary')
            ->size(Size::ExtraSmall)
            ->visible(fn (LayoutBuilder $livewire): bool => $livewire->canEditContent())
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
                fn (LayoutBuilder $livewire, Schema $schema, array $arguments): Schema => $this->getBlockAssetSchema(
                    $schema->operation('editOption')
                        ->record(fn (): WidgetAsset => $this->resolveEditableBlockAsset($arguments)),
                ),
            )
            ->fillForm(fn (WidgetAsset $record, array $arguments): array => [
                'meta' => $record->meta,
            ])
            ->record(fn (array $arguments): WidgetAsset => $this->resolveEditableBlockAsset($arguments))
            ->disabled(fn (WidgetAsset $record): bool => ! $record->exists)
            ->action(
                fn (WidgetAsset $record, array $data, LayoutBuilder $livewire, array $arguments, Action $action, Schema $schema) => $this->applyBlockAssetUpdate(
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
                (int) $arguments['blockIndex'],
                (int) $arguments['assetIndex'],
            ))
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments): void {
                $livewire->moveAssetUp($arguments['containerKey'], (int) $arguments['blockIndex'], (int) $arguments['assetIndex']);

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
                (int) $arguments['blockIndex'],
                (int) $arguments['assetIndex'],
            ))
            ->action(function (Action $action, LayoutBuilder $livewire, array $arguments): void {
                $livewire->moveAssetDown($arguments['containerKey'], (int) $arguments['blockIndex'], (int) $arguments['assetIndex']);

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
                    sprintf("selectedRecords['%s'][%s].length", $arguments['containerKey'], $arguments['blockIndex']),
                ),
            ])
            ->successNotificationTitle(__('capell-layout-builder::message.assets_removed_save_layout'))
            ->action(function (LayoutBuilder $livewire, array $arguments, Action $action): void {
                $selectedAssets = $livewire->getSelectedAssets($arguments['containerKey'], $arguments['blockIndex']);

                if ($selectedAssets === []) {
                    Notification::make('no-assets-selected')
                        ->body(__('capell-layout-builder::message.no_assets_selected'))
                        ->warning()
                        ->send();

                    $action->halt();
                }

                $livewire->removeSelectedAssets($arguments['containerKey'], $arguments['blockIndex']);

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
                        blockIndex: $arguments['blockIndex'],
                    );

                    return $hasPageAssets
                        ? __('capell-layout-builder::button.convert_block_assets')
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

                $block = $livewire->getContainerBlock($arguments['containerKey'], $arguments['blockIndex']);

                $assetTypes = isset($block->admin['asset_types']) && $block->admin['asset_types'] !== []
                    ? $block->admin['asset_types']
                    : ($block->type->admin['asset_types'] ?? null);

                if ($assetTypes === null) {
                    return false;
                }

                $assets = $livewire->getBlockAssets(
                    $arguments['containerKey'],
                    $arguments['blockIndex'],
                );

                if ($assets === []) {
                    return false;
                }

                $hasPageAssets = $livewire->blockHasPageAssets($block);

                $hasGlobalAssets = $livewire->blockHasGlobalAssets($block);

                return ! $hasPageAssets || ! $hasGlobalAssets;
            })
            ->requiresConfirmation()
            ->modalDescription(
                function (LayoutBuilder $livewire, array $arguments): string {
                    $hasPageAssets = $livewire->hasPageAssets(
                        containerKey: $arguments['containerKey'],
                        blockIndex: $arguments['blockIndex'],
                    );

                    return $hasPageAssets
                        ? __('capell-admin::generic.convert_block_assets')
                        : __('capell-admin::generic.convert_page_assets');
                },
            )
            ->action(function (LayoutBuilder $livewire, array $arguments, Action $action): void {
                $this->livewire->ensureLoaded();

                $hasPageAssets = $livewire->hasPageAssets(
                    containerKey: $arguments['containerKey'],
                    blockIndex: $arguments['blockIndex'],
                );

                $livewire->togglePageAssets(
                    $arguments['containerKey'],
                    $arguments['blockIndex'],
                    page: $hasPageAssets ? $livewire->page : null,
                );

                $action->success();
            });
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
        $blockIndex = $arguments['blockIndex'];
        $type = $arguments['type'];

        $hasPageAssets = $this->livewire->shouldAddPageAssets($containerKey, $blockIndex);

        $block = $this->livewire->getContainerBlock($containerKey, $blockIndex);

        $order = $this->livewire->countBlockAssets($containerKey, $blockIndex) + 1;

        /** @var WidgetAsset $blockAsset */
        $blockAsset = $configurator->getRecord();

        // Fake exists to ensure assets relations are saved correctly
        $blockAsset->exists = true;
        $blockAsset->wasRecentlyCreated = true; // prevent MissingAttributeException

        $data['widget_id'] = $block->id;
        $draftableNewAssetWorkspace = $this->workspaceForNewDraftableAsset($blockAsset, $type);
        $draftableNewAsset = $this->createDraftableAssetFromBuilderData($type, $data, $draftableNewAssetWorkspace);

        if ($draftableNewAsset instanceof Model) {
            $blockAsset->asset_id = $draftableNewAsset->getKey();
            $blockAsset->setRelation('asset', $draftableNewAsset);
        }

        // Ensure UpdatedModelAction is not triggered
        WidgetAsset::withoutEvents(function () use ($blockAsset, $configurator, $draftableNewAssetWorkspace): void {
            if ($draftableNewAssetWorkspace instanceof Workspace) {
                WorkspaceContext::runWith($draftableNewAssetWorkspace, function () use ($blockAsset, $configurator, $draftableNewAssetWorkspace): void {
                    $configurator->saveRelationships();
                    $this->moveCreatedDraftableAssetIntoWorkspace($blockAsset, $draftableNewAssetWorkspace);
                });

                return;
            }

            $configurator->saveRelationships();
        });

        if ($draftableNewAssetWorkspace instanceof Workspace && ! (WorkspaceContext::current() instanceof Workspace)) {
            Notification::make('created-asset-draft')
                ->title(__('capell-layout-builder::message.asset_draft_saved', ['workspace' => $draftableNewAssetWorkspace->name]))
                ->success()
                ->send();

            $this->livewire->dispatch('workspace-changed', workspaceId: $draftableNewAssetWorkspace->id);

            $action->success();

            return;
        }

        if (! isset($this->livewire->assets[$containerKey][$blockIndex])) {
            $this->livewire->assets[$containerKey][$blockIndex] = [];
        }

        $assetId = $blockAsset->asset_id;

        $block = $this->livewire->getContainerBlock($containerKey, $blockIndex);

        $occurrence = $this->livewire->getContainerBlockOccurrence($containerKey, $blockIndex);

        $meta = $data[$assetId] ?? [];

        $asset = [
            'asset_id' => $assetId,
            'asset_type' => $type,
            'meta' => $meta,
            'widget_id' => $block->id,
            'order' => $order,
            'occurrence' => $occurrence,
        ];

        if ($hasPageAssets) {
            $asset['pageable_id'] = $this->livewire->page->getKey();
            $asset['pageable_type'] = $this->livewire->page->getMorphClass();
            $asset['container'] = $containerKey;
        }

        $this->livewire->assets[$containerKey][$blockIndex][] = $asset;

        $blockAsset->load([
            'asset' => fn (MorphTo $query): MorphTo => $query->morphWith($this->livewire->getAssetRelations()),
        ]);

        $blockAsset->setRelation('block', $block);

        $block->assets->add($blockAsset);

        $this->livewire->layoutUpdated();

        $action->success();

        $this->livewire->dispatch(
            'refresh-assets',
            containerKey: $containerKey,
            blockIndex: $blockIndex,
        );
    }

    private function moveCreatedDraftableAssetIntoWorkspace(WidgetAsset $blockAsset, Workspace $workspace): void
    {
        $asset = $blockAsset->getRelationValue('asset');

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

    private function workspaceForNewDraftableAsset(WidgetAsset $blockAsset, string $type): ?Workspace
    {
        if (! class_exists(CreateRecordDraftWorkspaceAction::class) || ! class_exists(WorkspaceRegistry::class)) {
            return null;
        }

        $activeWorkspace = WorkspaceContext::current();

        if ($activeWorkspace instanceof Workspace) {
            return $activeWorkspace;
        }

        try {
            $asset = CapellCore::getAsset($type);
        } catch (Throwable) {
            return null;
        }

        $modelClass = $asset->model ?? null;

        if (! is_string($modelClass) || ! is_a($modelClass, Model::class, true) || ! WorkspaceRegistry::isRegistered($modelClass)) {
            return null;
        }

        $user = auth()->user();

        if (! $user instanceof AuthenticatedUser) {
            return null;
        }

        $record = $blockAsset->getRelationValue('asset');

        if (! $record instanceof Model) {
            $record = new $modelClass;
        }

        return CreateRecordDraftWorkspaceAction::run($record, $user);
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function createDraftableAssetFromBuilderData(string $type, array $data, ?Workspace $workspace): ?Model
    {
        if (! $workspace instanceof Workspace || ! class_exists(WorkspaceRegistry::class)) {
            return null;
        }

        try {
            $asset = CapellCore::getAsset($type);
        } catch (Throwable) {
            return null;
        }

        $modelClass = $asset->model ?? null;

        if (! is_string($modelClass) || ! is_a($modelClass, Model::class, true) || ! WorkspaceRegistry::isRegistered($modelClass)) {
            return null;
        }

        $assetData = $data['asset'] ?? [];

        if (! is_array($assetData)) {
            $assetData = [];
        }

        /** @var Model $record */
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
    private function makeBlockAssetRecordForCreate(array $arguments): WidgetAsset
    {
        $containerKey = $arguments['containerKey'];
        $blockIndex = $arguments['blockIndex'];
        $assetType = $arguments['type'];

        $block = $this->livewire->getContainerBlock($containerKey, $blockIndex);

        /** @var class-string<WidgetAsset> $model */
        $model = WidgetAsset::class;

        $record = $model::query()->make([
            'widget_id' => $block->id,
            'workspace_id' => $this->livewire->getCurrentBlockAssetWorkspaceId($block),
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
    private function resolveEditableBlockAsset(array $arguments): WidgetAsset
    {
        $containerKey = $arguments['containerKey'];
        $blockIndex = $arguments['blockIndex'];
        $index = $arguments['index'];
        $type = $arguments['type'];

        $block = $this->livewire->getContainerBlock($containerKey, $blockIndex);
        $asset = $this->livewire->getBlockAsset($containerKey, $blockIndex, $index);

        throw_unless($asset, MissingBlockAssetException::class, $block, $type, $index, $arguments);

        $assetId = $asset['asset_id'];

        $blockAsset = isset($asset['id'])
            ? $block->assets->first(fn (WidgetAsset $blockAsset): bool => (int) $blockAsset->getKey() === (int) $asset['id'])
            : null;

        $blockAsset ??= $block->assets
            ->where('asset_type', $type)
            ->where('asset_id', $assetId)
            ->first();

        throw_unless($blockAsset, Exception::class, sprintf('Asset of type [%s] with ID [%s] not found.', $type, $assetId));
        throw_unless((int) $blockAsset->getAttribute('widget_id') === (int) $block->getKey(), Exception::class, sprintf('Asset of type [%s] with ID [%s] is not attached to this block.', $type, $assetId));

        return $blockAsset;
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     */
    private function getEditWidgetAssetModalHeading(LayoutBuilder $livewire, array $arguments): string
    {
        $name = str($arguments['type'])->title();

        if ($livewire->inPageContext()) {
            return __('capell-layout-builder::heading.edit_page_block_asset', ['name' => (string) $name]);
        }

        return __('capell-layout-builder::heading.edit_block_asset', ['name' => (string) $name]);
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     */
    private function getEditWidgetAssetModalDescription(LayoutBuilder $livewire, array $arguments): ?string
    {
        if (! $livewire->inPageContext()) {
            return null;
        }

        $blockAsset = $this->livewire->getBlockAsset($arguments['containerKey'], $arguments['blockIndex'], $arguments['index']);

        if (! isset($blockAsset['pageable_id'], $blockAsset['pageable_type'])) {
            return null;
        }

        return __('capell-layout-builder::heading.page_block_asset', ['name' => $livewire->page->name]);
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     * @param  array<array-key, mixed>  $data
     */
    private function applyBlockAssetUpdate(WidgetAsset $record, array $data, LayoutBuilder $livewire, array $arguments, Action $action, Schema $configurator): void
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

        $block = $this->livewire->getContainerBlock($arguments['containerKey'], $arguments['blockIndex']);
        $canUpdatePersistedRecord = $record->workspace_id === $this->livewire->getCurrentBlockAssetWorkspaceId($block);
        $assetDrafted = $this->saveDraftableAssetFromBlockAsset($record, $data, $canUpdatePersistedRecord);

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
            $livewire->updateBlockAssetContentState($arguments['containerKey'], $arguments['blockIndex'], $arguments['index'], ['meta' => $data['meta']]);
        }

        $livewire->reloadContainerBlockAsset($arguments['containerKey'], $arguments['blockIndex'], $arguments['index']);
        $this->restorePendingLiveDraftableAssetSnapshot();
        $livewire->layoutUpdated();

        $action->success();
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function saveDraftableAssetFromBlockAsset(WidgetAsset $record, array $data, bool $canUpdatePersistedRecord): bool
    {
        if (! class_exists(SaveRecordDraftAction::class) || ! class_exists(WorkspaceRegistry::class)) {
            return false;
        }

        $asset = $record->asset;

        if (! $asset instanceof Model || ! WorkspaceRegistry::isRegistered($asset::class)) {
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

        $workspace = WorkspaceContext::current();
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

        $result = SaveRecordDraftAction::run(
            record: $asset,
            data: $assetData,
            user: $user,
            workspace: $workspace instanceof Workspace ? $workspace : null,
            saveRelationships: fn (Model $draft): null => $this->saveDraftableAssetRelationData($draft, $assetData),
        );

        if ($liveSnapshot !== null) {
            $this->restoreLiveDraftableAssetSnapshot($asset, $liveSnapshot, $result->workspace);
            $this->pendingLiveDraftableWorkspace = $result->workspace;
        }

        if ($liveRelationSnapshot !== null) {
            $this->restoreLiveDraftableAssetRelationSnapshot($asset, $liveRelationSnapshot);
        }

        if ($canUpdatePersistedRecord && (int) $record->workspace_id > 0) {
            $record->asset_id = $result->record->getKey();
            $record->save();
        }

        if (! (WorkspaceContext::current() instanceof Workspace)) {
            $this->livewire->dispatch('workspace-changed', workspaceId: $result->workspace->id);
        }

        Notification::make('saved-asset-draft')
            ->title(__('capell-layout-builder::message.asset_draft_saved', ['workspace' => $result->workspace->name]))
            ->success()
            ->send();

        return true;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function restoreLiveDraftableAssetSnapshot(Model $asset, array $snapshot, Workspace $workspace): void
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
        if ($this->pendingLiveDraftableAssetSnapshot === null || $this->pendingLiveDraftableWorkspace === null) {
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

        /** @var Model $asset */
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

    private function getBlockAssetSchema(Schema $configurator): Schema
    {
        return WidgetAssetForm::configure($configurator);
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     */
    private function isDraftableAssetType(array $arguments): bool
    {
        if (! class_exists(WorkspaceRegistry::class)) {
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

        return is_string($modelClass) && is_a($modelClass, Model::class, true) && WorkspaceRegistry::isRegistered($modelClass);
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
    }
}
