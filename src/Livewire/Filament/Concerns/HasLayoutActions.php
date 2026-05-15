<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Concerns;

use BackedEnum;
use Capell\Admin\Actions\ReplicateLayoutAction;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Exceptions\MissingElementAssetException;
use Capell\LayoutBuilder\Filament\Resources\Elements\Schemas\ElementAssetForm;
use Capell\LayoutBuilder\Filament\Resources\Elements\Schemas\ElementForm;
use Capell\LayoutBuilder\Filament\Resources\Elements\Tables\ElementSelectionTable;
use Capell\LayoutBuilder\Filament\Resources\Pages\Tables\PageSelectionTable;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
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

    public function editLayoutElementAction(): Action
    {
        return Action::make('editLayoutElement')
            ->label(__('capell-layout-builder::button.edit_layout_element'))
            ->groupedIcon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->grouped()
            ->visible(
                fn (array $arguments, self $livewire): bool => (bool) $livewire->getContainerElementConfigurator(
                    $arguments['containerKey'],
                    $arguments['elementIndex'],
                ),
            )
            ->modalHeading(__('capell-layout-builder::heading.container_element_settings'))
            ->modalSubmitActionLabel(fn (Action $action): string => $action->getLabel())
            ->modalDescription(
                fn (array $arguments, self $livewire): string => __(
                    'capell-admin::generic.edit_container_element',
                    [
                        'container' => $arguments['containerKey'],
                        'element' => $livewire->getContainerElement($arguments['containerKey'], $arguments['elementIndex'])?->name,
                    ],
                ),
            )
            ->modalWidth(Width::ScreenSmall)
            ->schema(function (array $arguments, self $livewire, Schema $schema): Schema {
                $adminSchema = AdminSurfaceLookup::configurator(
                    ConfiguratorTypeEnum::LayoutElement->value,
                    $livewire->getContainerElementConfigurator($arguments['containerKey'], $arguments['elementIndex']),
                );

                $typeSchema = resolve($adminSchema)->make($schema);

                return $schema->operation('editOption')->components($typeSchema);
            })
            ->fillForm(
                fn (self $livewire, array $arguments): array => $livewire->containers[$arguments['containerKey']]['elements'][$arguments['elementIndex']]['meta'] ?? [],
            )
            ->action(function (Action $action, self $livewire, array $arguments, array $data): void {
                $livewire->editLayoutElement($arguments['containerKey'], $arguments['elementIndex'], $data);

                $action->success();
            });
    }

    public function addElementAction(): Action
    {
        return Action::make('addElement')
            ->label(fn (array $arguments): string => isset($arguments['position'])
                ? __('capell-layout-builder::button.add_element_here')
                : __('capell-layout-builder::button.add_element'))
            ->tooltip(__('capell-layout-builder::button.add_element'))
            ->modalHeading(__('capell-layout-builder::heading.add_element_to_container'))
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

                $components[] = TableSelect::make('elements')
                    ->label(__('capell-layout-builder::button.element'))
                    ->tableConfiguration(ElementSelectionTable::class)
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

                $livewire->addElementsToContainer(
                    containerKey: (string) $containerKey,
                    elements: $data['elements'] ?? [],
                    position: isset($arguments['position']) ? (int) $arguments['position'] : null,
                );
            });
    }

    public function editElementAction(): Action
    {
        return Action::make('editElement')
            ->label(__('capell-layout-builder::button.edit_element'))
            ->tooltip(__('capell-layout-builder::button.edit_element'))
            ->button()
            ->slideOver()
            ->closeModalByClickingAway(false)
            ->icon('heroicon-o-pencil')
            ->color('gray')
            ->size(Size::Small)
            ->modalWidth(Width::ScreenLarge)
            ->record(
                fn (array $arguments): Element => $this->getContainerElement(
                    $arguments['containerKey'],
                    $arguments['elementIndex'],
                ),
            )
            ->modalHeading(fn (Element $record): string => $record->name)
            ->modalDescription(
                fn (Element $record): string => __(
                    'capell-layout-builder::heading.element_type',
                    ['type' => $record->type?->name],
                ),
            )
            ->modalSubmitActionLabel(__('capell-layout-builder::button.save_changes'))
            ->successNotificationTitle(__('capell-layout-builder::message.element_updated'))
            ->fillForm(fn (Element $record): array => $record->attributesToArray())
            ->schema(
                fn (Action $action, Schema $schema): Schema => ElementForm::configure(
                    $schema->operation('editOption')
                        ->record(fn (): Element => $action->getRecord()->fresh()),
                ),
            )
            ->action(function (Action $action, Element $record, Schema $schema, array $data): void {
                $this->saveElementForm(configurator: $schema, record: $record, data: $data);

                $action->success();
            });
    }

    public function duplicateElementAction(): Action
    {
        return Action::make('duplicateElement')
            ->label(__('capell-layout-builder::button.duplicate_element'))
            ->grouped()
            ->groupedIcon('heroicon-o-square-2-stack')
            ->color('gray')
            ->size('sm')
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->duplicateElement(containerKey: $arguments['containerKey'], originalIndex: $arguments['elementIndex']);

                $action->success();
            });
    }

    public function moveElementUpAction(): Action
    {
        return Action::make('moveElementUp')
            ->label(__('capell-layout-builder::button.move_up'))
            ->grouped()
            ->groupedIcon('heroicon-o-arrow-up')
            ->color('gray')
            ->size(Size::Small)
            ->visible(fn (array $arguments, self $livewire): bool => $livewire->canMoveElementUp(
                $arguments['containerKey'],
                $arguments['elementIndex'],
            ))
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->moveElementUp($arguments['containerKey'], $arguments['elementIndex']);

                $action->success();
            });
    }

    public function moveElementDownAction(): Action
    {
        return Action::make('moveElementDown')
            ->label(__('capell-layout-builder::button.move_down'))
            ->grouped()
            ->groupedIcon('heroicon-o-arrow-down')
            ->color('gray')
            ->size(Size::Small)
            ->visible(fn (array $arguments, self $livewire): bool => $livewire->canMoveElementDown(
                $arguments['containerKey'],
                $arguments['elementIndex'],
            ))
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->moveElementDown($arguments['containerKey'], $arguments['elementIndex']);

                $action->success();
            });
    }

    public function moveElementToContainerAction(): Action
    {
        return Action::make('moveElementToContainer')
            ->label(__('capell-layout-builder::button.move_to_container'))
            ->grouped()
            ->groupedIcon('heroicon-o-arrow-right')
            ->color('gray')
            ->size(Size::Small)
            ->modalWidth(Width::ScreenSmall)
            ->modalHeading(__('capell-layout-builder::button.move_to_container'))
            ->visible(fn (array $arguments, self $livewire): bool => $livewire->canMoveElementToAnotherContainer(
                $arguments['containerKey'],
                $arguments['elementIndex'],
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
                $livewire->moveElementToContainer(
                    $arguments['containerKey'],
                    $arguments['elementIndex'],
                    (string) $data['target_container'],
                );

                $action->success();
            });
    }

    public function removeElementAction(): Action
    {
        return Action::make('removeElement')
            ->label(__('capell-layout-builder::button.remove_element'))
            ->grouped()
            ->groupedIcon('heroicon-m-trash')
            ->color('danger')
            ->size('sm')
            ->requiresConfirmation()
            ->modalDescription(__('capell-layout-builder::message.remove_element_confirmation'))
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->removeElement(containerKey: $arguments['containerKey'], elementIndex: $arguments['elementIndex']);

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
                $totalAssets = $livewire->countElementAssets($arguments['containerKey'], $arguments['elementIndex']);

                if ($totalAssets !== 0) {
                    $hasPageAssets = $livewire->hasPageAssets($arguments['containerKey'], $arguments['elementIndex']);
                } else {
                    $hasPageAssets = $livewire->inPageContext();
                }

                return $hasPageAssets
                    ? __('capell-admin::generic.select_page_element_asset_description', ['type' => $arguments['type']])
                    : __('capell-admin::generic.select_element_asset_description', ['type' => $arguments['type']]);
            })
            ->closeModalByClickingAway(false)
            ->schema(function (Schema $schema, array $arguments, self $livewire): Schema {
                $tableConfiguration = PageSelectionTable::class;

                $excludeIds = $livewire->getElementAssetsByType(
                    $arguments['containerKey'],
                    (int) $arguments['elementIndex'],
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
                $elementIndex = (int) $arguments['elementIndex'];
                $type = $arguments['type'];

                $hasPageAssets = $livewire->countElementAssets($containerKey, $elementIndex) > 0
                    ? $livewire->hasPageAssets($containerKey, $elementIndex)
                    : $livewire->inPageContext();

                $livewire->addAssetsToElement(
                    arguments: [
                        'containerKey' => $containerKey,
                        'elementIndex' => $elementIndex,
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
                    'capell-admin::generic.add_element_asset',
                    [
                        'element' => $livewire->getContainerElement($arguments['containerKey'], $arguments['elementIndex'])?->name,
                        'asset' => $arguments['type'],
                    ],
                ),
            )
            ->modalSubmitActionLabel(
                fn (array $arguments, Action $action): string => __(
                    'capell-layout-builder::button.create_element_asset',
                    ['type' => $arguments['type']],
                ),
            )
            ->successNotificationTitle(__('capell-layout-builder::message.asset_added'))
            ->schema(
                fn (array $arguments, Schema $schema): Schema => self::getElementAssetSchema(
                    $schema->operation('createOption')
                        ->record(fn (): ElementAsset => $this->makeElementAssetRecordForCreate($arguments)),
                ),
            )
            ->model(fn (): string => ElementAsset::class)
            ->fillForm(function (array $arguments): array {
                $containerKey = $arguments['containerKey'];
                $elementIndex = $arguments['elementIndex'];
                $assetType = $arguments['type'];

                $element = $this->getContainerElement($containerKey, $elementIndex);

                $asset = CapellAdmin::getAsset($assetType);

                return [
                    'layout_element_id' => $element->id,
                    'workspace_id' => $this->getCurrentElementAssetWorkspaceId($element),
                    'asset_type' => $assetType,
                    'meta' => [],
                    'asset' => in_array($asset->defaultDataAction, [null, '', '0'], true)
                        ? []
                        : $asset->defaultDataAction::run(),
                ];
            })
            ->action(self::addAssetFromAction(...));
    }

    public function editElementAssetAction(): Action
    {
        return Action::make('editElementAsset')
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
                fn (self $livewire, array $arguments): string => $this->getEditElementAssetModalHeading($livewire, $arguments),
            )
            ->modalDescription(
                fn (self $livewire, array $arguments): ?string => $this->getEditElementAssetModalDescription($livewire, $arguments),
            )
            ->modalSubmitActionLabel(__('capell-layout-builder::button.save_changes'))
            ->successNotificationTitle(__('capell-layout-builder::message.asset_updated'))
            ->schema(
                fn (self $livewire, Schema $schema, array $arguments): Schema => self::getElementAssetSchema(
                    $schema->operation('editOption'),
                ),
            )
            ->fillForm(fn (ElementAsset $record, array $arguments): array => [
                'meta' => $record->meta,
            ])
            ->record(fn (array $arguments): ElementAsset => $this->resolveEditableElementAsset($arguments))
            ->disabled(fn (ElementAsset $record): bool => ! $record->exists)
            ->action(
                fn (ElementAsset $record, array $data, self $livewire, array $arguments, Action $action, Schema $schema) => $this->applyElementAssetUpdate(
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
                (int) $arguments['elementIndex'],
                (int) $arguments['assetIndex'],
            ))
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->moveAssetUp($arguments['containerKey'], (int) $arguments['elementIndex'], (int) $arguments['assetIndex']);

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
                (int) $arguments['elementIndex'],
                (int) $arguments['assetIndex'],
            ))
            ->action(function (Action $action, self $livewire, array $arguments): void {
                $livewire->moveAssetDown($arguments['containerKey'], (int) $arguments['elementIndex'], (int) $arguments['assetIndex']);

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
                    sprintf("selectedRecords['%s'][%s].length", $arguments['containerKey'], $arguments['elementIndex']),
                ),
            ])
            ->successNotificationTitle(__('capell-layout-builder::message.assets_removed_save_layout'))
            ->action(function (self $livewire, array $arguments, Action $action): void {
                $selectedAssets = $livewire->getSelectedAssets($arguments['containerKey'], $arguments['elementIndex']);

                if ($selectedAssets === []) {
                    Notification::make('no-assets-selected')
                        ->body(__('capell-layout-builder::message.no_assets_selected'))
                        ->warning()
                        ->send();

                    $action->halt();
                }

                $livewire->removeSelectedAssets($arguments['containerKey'], $arguments['elementIndex']);

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
                        elementIndex: $arguments['elementIndex'],
                    );

                    return $hasPageAssets
                        ? __('capell-layout-builder::button.convert_element_assets')
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

                $element = $livewire->getContainerElement($arguments['containerKey'], $arguments['elementIndex']);

                $assetTypes = isset($element->admin['asset_types']) && $element->admin['asset_types'] !== []
                    ? $element->admin['asset_types']
                    : ($element->type->admin['asset_types'] ?? null);

                if ($assetTypes === null) {
                    return false;
                }

                $assets = $livewire->getElementAssets(
                    $arguments['containerKey'],
                    $arguments['elementIndex'],
                );

                if ($assets === []) {
                    return false;
                }

                $hasPageAssets = $livewire->elementHasPageAssets($element);

                $hasGlobalAssets = $livewire->elementHasGlobalAssets($element);

                return ! $hasPageAssets || ! $hasGlobalAssets;
            })
            ->requiresConfirmation()
            ->modalDescription(
                function (self $livewire, array $arguments): string {
                    $hasPageAssets = $livewire->hasPageAssets(
                        containerKey: $arguments['containerKey'],
                        elementIndex: $arguments['elementIndex'],
                    );

                    return $hasPageAssets
                        ? __('capell-admin::generic.convert_element_assets')
                        : __('capell-admin::generic.convert_page_assets');
                },
            )
            ->action(function (self $livewire, array $arguments, Action $action): void {
                $this->ensureLoaded();

                $hasPageAssets = $livewire->hasPageAssets(
                    containerKey: $arguments['containerKey'],
                    elementIndex: $arguments['elementIndex'],
                );

                $livewire->togglePageAssets(
                    $arguments['containerKey'],
                    $arguments['elementIndex'],
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
        $elementIndex = $arguments['elementIndex'];
        $type = $arguments['type'];

        $hasPageAssets = $this->shouldAddPageAssets($containerKey, $elementIndex);

        $element = $this->getContainerElement($containerKey, $elementIndex);

        $order = $this->countElementAssets($containerKey, $elementIndex) + 1;

        /** @var ElementAsset $elementAsset */
        $elementAsset = $configurator->getRecord();

        // Fake exists to ensure assets relations are saved correctly
        $elementAsset->exists = true;
        $elementAsset->wasRecentlyCreated = true; // prevent MissingAttributeException

        $data['layout_element_id'] = $element->id;

        // Ensure UpdatedModelAction is not triggered
        ElementAsset::withoutEvents(function () use ($configurator): void {
            $configurator->saveRelationships();
        });

        if (! isset($this->assets[$containerKey][$elementIndex])) {
            $this->assets[$containerKey][$elementIndex] = [];
        }

        $assetId = $elementAsset->asset_id;

        $element = $this->getContainerElement($containerKey, $elementIndex);

        $occurrence = $this->getContainerElementOccurrence($containerKey, $elementIndex);

        $meta = $data[$assetId] ?? [];

        $asset = [
            'asset_id' => $assetId,
            'asset_type' => $type,
            'meta' => $meta,
            'layout_element_id' => $element->id,
            'order' => $order,
            'occurrence' => $occurrence,
        ];

        if ($hasPageAssets) {
            $asset['pageable_id'] = $this->page->getKey();
            $asset['pageable_type'] = $this->page->getMorphClass();
            $asset['container'] = $containerKey;
        }

        $this->assets[$containerKey][$elementIndex][] = $asset;

        $elementAsset->load([
            'asset' => fn (MorphTo $query): MorphTo => $query->morphWith($this->getAssetRelations()),
        ]);

        $elementAsset->setRelation('element', $element);

        $element->assets->add($elementAsset);

        $this->layoutUpdated();

        $action->success();

        $this->dispatch(
            'refresh-assets',
            containerKey: $containerKey,
            elementIndex: $elementIndex,
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

    protected function makeElementAssetRecordForCreate(array $arguments): ElementAsset
    {
        $containerKey = $arguments['containerKey'];
        $elementIndex = $arguments['elementIndex'];
        $assetType = $arguments['type'];

        $element = $this->getContainerElement($containerKey, $elementIndex);

        /** @var class-string<ElementAsset> $model */
        $model = ElementAsset::class;

        $record = $model::query()->make([
            'layout_element_id' => $element->id,
            'workspace_id' => $this->getCurrentElementAssetWorkspaceId($element),
            'asset_type' => $assetType,
            'meta' => [],
        ]);

        $asset = CapellCore::getAsset($assetType)->model::make();

        $record->setRelation('asset', $asset);

        return $record;
    }

    protected function resolveEditableElementAsset(array $arguments): ElementAsset
    {
        $containerKey = $arguments['containerKey'];
        $elementIndex = $arguments['elementIndex'];
        $index = $arguments['index'];
        $type = $arguments['type'];

        $element = $this->getContainerElement($containerKey, $elementIndex);
        $asset = $this->getElementAsset($containerKey, $elementIndex, $index);

        throw_unless($asset, MissingElementAssetException::class, $element, $type, $index, $arguments);

        $assetId = $asset['asset_id'];

        $elementAsset = isset($asset['id'])
            ? $element->assets->first(fn (ElementAsset $elementAsset): bool => (int) $elementAsset->getKey() === (int) $asset['id'])
            : null;

        $elementAsset ??= $element->assets
            ->where('asset_type', $type)
            ->where('asset_id', $assetId)
            ->first();

        throw_unless($elementAsset, Exception::class, sprintf('Asset of type [%s] with ID [%s] not found.', $type, $assetId));
        throw_unless((int) $elementAsset->getAttribute('layout_element_id') === (int) $element->getKey(), Exception::class, sprintf('Asset of type [%s] with ID [%s] is not attached to this element.', $type, $assetId));

        return $elementAsset;
    }

    protected function getEditElementAssetModalHeading(self $livewire, array $arguments): string
    {
        $name = str($arguments['type'])->title();

        if ($livewire->inPageContext()) {
            return __('capell-layout-builder::heading.edit_page_element_asset', ['name' => $name]);
        }

        return __('capell-layout-builder::heading.edit_element_asset', ['name' => $name]);
    }

    protected function getEditElementAssetModalDescription(self $livewire, array $arguments): ?string
    {
        if (! $livewire->inPageContext()) {
            return null;
        }

        $elementAsset = $this->getElementAsset($arguments['containerKey'], $arguments['elementIndex'], $arguments['index']);

        if (! isset($elementAsset['pageable_id'], $elementAsset['pageable_type'])) {
            return null;
        }

        return __('capell-layout-builder::heading.page_element_asset', ['name' => $livewire->page->name]);
    }

    protected function applyElementAssetUpdate(ElementAsset $record, array $data, self $livewire, array $arguments, Action $action, Schema $configurator): void
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

        $element = $this->getContainerElement($arguments['containerKey'], $arguments['elementIndex']);
        $canUpdatePersistedRecord = $record->workspace_id === $this->getCurrentElementAssetWorkspaceId($element);

        if ($canUpdatePersistedRecord) {
            $configurator->saveRelationships();
        }

        if ($data !== [] && $canUpdatePersistedRecord) {
            $record->update($data);
        }

        if (isset($data['meta'])) {
            $livewire->updateElementAssetContentState($arguments['containerKey'], $arguments['elementIndex'], $arguments['index'], ['meta' => $data['meta']]);
        }

        $livewire->reloadContainerElementAsset($arguments['containerKey'], $arguments['elementIndex'], $arguments['index']);

        $action->success();
    }

    protected function getElementAssetSchema(Schema $configurator): Schema
    {
        return ElementAssetForm::configure($configurator);
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

    protected function saveElementForm(Schema $configurator, Element $record, array $data): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        $configurator->saveRelationships();

        $record->update($data);
    }
}
