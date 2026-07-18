<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Aimeos\Nestedset\NestedSet;
use Capell\Admin\Actions\GetAssetResourceUrlAction;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Components\Forms\SelectWithBelongsToRelation;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Data\AssetData;
use Capell\Core\Enums\BlueprintGroupEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Contracts\Extenders\WidgetAssetSchemaExtender;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use RuntimeException;

class AssetsRepeater extends Repeater
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->relationship()
            ->orderColumn('order')
            ->defaultItems(1)
            ->table([
                TableColumn::make(__('capell-admin::form.asset')),
            ])
            ->addAction(self::modifyAddAction(...))
            ->schema(self::getFormSchema())
            ->extraItemActions([
                Action::make('edit_asset')
                    ->visible(
                        fn (array $arguments, Repeater $component): bool => filled(
                            $component->getRawItemState($arguments['item'])['asset_id'],
                        ),
                    )
                    ->tooltip(function (array $arguments, Repeater $component): string {
                        $itemData = $component->getRawItemState($arguments['item']);

                        return __(
                            'capell-layout-builder::button.edit_asset_type',
                            ['type' => $itemData['asset_type']],
                        );
                    })
                    ->icon(Heroicon::PencilSquare)
                    ->url(function (array $arguments, Repeater $component): ?string {
                        $itemData = $component->getRawItemState($arguments['item']);

                        return GetAssetResourceUrlAction::run($itemData['asset_type'], $itemData['asset_id']);
                    }),
            ])
            ->registerActions([
                fn (self $component): Action => $component->getAddAssetAction(),
            ]);
    }

    public function getAddAssetAction(): Action
    {
        return Action::make('add_asset')
            ->action(function (Repeater $component, array $arguments): void {
                $newUuid = $component->generateUuid();

                $items = $component->getRawState();

                if (! in_array($newUuid, [null, '', '0'], true)) {
                    $items[$newUuid] = $arguments;
                } else {
                    $items[] = $arguments;
                }

                $component->rawState($items);

                $childSchema = $component->getChildSchema($newUuid ?? array_key_last($items));

                if ($childSchema instanceof Schema) {
                    $childSchema->fill($arguments);
                }

                $component->collapsed(false, shouldMakeComponentCollapsible: false);

                $component->callAfterStateUpdated();

                $component->partiallyRender();
            });
    }

    /**
     * @return array<array-key, mixed>
     */
    protected static function getFormSchema(): array
    {
        $select = SelectWithBelongsToRelation::make('asset_id');

        $createOptionUsing = $select->getCreateOptionUsing();

        $components = [
            Hidden::make('asset_type'),
            $select
                ->label(__('capell-layout-builder::form.select_add_asset_type'))
                ->required()
                ->searchable()
                ->relationship(
                    'asset',
                    'name',
                    modifyQueryUsing: self::modifyAssetQuery(...),
                )
                ->savesBelongsToRelation()
                ->getSelectedRecordUsing(
                    function (Select $component, Get $get, mixed $state): ?Model {
                        if ($state === null) {
                            return null;
                        }

                        $asset = CapellCore::getAsset(self::assetType($get));

                        return $asset->model::withTrashed()->find($state);
                    },
                )
                ->placeholder(
                    fn (Get $get): string => __(
                        'capell-admin::generic.select_asset_placeholder',
                        ['asset' => CapellCore::getAsset(self::assetType($get))->getLabel()],
                    ),
                )
                ->prefixIcon(
                    fn (Get $get): null|string|Heroicon => CapellCore::getAsset(self::assetType($get))->getIcon(),
                )
                ->selectablePlaceholder(false)
                ->getOptionLabelFromRecordUsing(function (Select $component, Model $record): HtmlString {
                    if (! $record instanceof Pageable) {
                        $titleAttribute = $component->getRelationshipTitleAttribute();
                        $label = is_string($titleAttribute) ? $record->getAttribute($titleAttribute) : $record->getKey();

                        return new HtmlString((string) $label);
                    }

                    $siteName = $record->site?->name;
                    $label = is_string($siteName) && $siteName !== '' ? $siteName . ' &raquo; ' : '';

                    if ($record instanceof Page) {
                        $ancestors = $record->ancestors()->get();

                        if ($ancestors->isNotEmpty()) {
                            $label .= $ancestors->pluck('name')
                                ->map(fn (string $name): string => Str::limit($name, 30))
                                ->implode(' &raquo; ')
                                . ' &raquo; ';
                        }
                    }

                    return new HtmlString($label . Str::limit($record->name, 40));
                })
                ->createOptionForm(function (Schema $configurator, Get $get): Schema {
                    $asset = CapellCore::getAsset(self::assetType($get));

                    $assetAdmin = CapellAdmin::getAsset($get('asset_type'));

                    return $assetAdmin->formClass::configure(
                        $configurator->operation('createOption')->model(self::assetModelClass($asset)),
                    );
                })
                ->createOptionUsing(function (Select $component, Schema $configurator, Get $get, array $data) use ($createOptionUsing): int|string {
                    $asset = CapellAdmin::getAsset($get('asset_type'));

                    $record = in_array($asset->createAction, [null, '', '0'], true)
                        ? $component->evaluate($createOptionUsing)
                        : $asset->createAction::run($data);

                    $configurator->model($record)->saveRelationships();

                    Notification::make()
                        ->title(__('capell-layout-builder::message.page_created_successfully'))
                        ->body($record->name)
                        ->send();

                    return $record->getKey();
                })
                ->createOptionAction(function (Action $action, Get $get): Action {
                    $asset = CapellAdmin::getAsset($get('asset_type'));

                    return self::modifyCreateAction($action)
                        ->visible(fn (?int $state): bool => $state === null)
                        ->fillForm(fn (): array => in_array($asset->defaultDataAction, [null, '', '0'], true) ? [] : $asset->defaultDataAction::run());
                }),
        ];

        foreach (app()->tagged(WidgetAssetSchemaExtender::TAG) as $extender) {
            if ($extender instanceof WidgetAssetSchemaExtender) {
                $components = $extender->extendRepeaterComponents($components);
            }
        }

        return $components;
    }

    protected static function modifyAddAction(Action $action, self $component): Action
    {
        $actions = ActionGroup::make(
            CapellCore::getAssets()
                ->sortBy('name')
                ->map(
                    fn (AssetData $asset): Action => $component->getAddAssetAction()
                        ->schemaComponent($component)
                        ->label($asset->getLabel())
                        ->icon($asset->getIcon())
                        ->arguments(['asset_type' => $asset->getKey()]),
                )
                ->all(),
        )
            ->dropdownPlacement('bottom')
            ->label(fn (): string|Htmlable|null => $action->getLabel())
            ->icon(Heroicon::Plus);

        return $action->group($actions)
            ->view('capell-admin::components.actions.dropdown-group');
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected static function modifyAssetQuery(Builder $query, Get $get): Builder
    {
        if ($get('asset_type') !== 'page') {
            return $query;
        }

        return $query
            ->with([
                'ancestors',
                'site',
            ])
            ->orderBy('site_id')
            ->orderBy(NestedSet::LFT, 'DESC')
            ->whereHas('blueprint', self::applySelectablePageTypeQuery(...));
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected static function applySelectablePageTypeQuery(Builder $query): Builder
    {
        return $query->where(self::applyNonSystemBlueprintGroupQuery(...));
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected static function applyNonSystemBlueprintGroupQuery(Builder $query): Builder
    {
        return $query
            ->where('group', '!=', BlueprintGroupEnum::System->value)
            ->orWhereNull('group');
    }

    private static function assetType(Get $get): string
    {
        $assetType = $get('asset_type');

        throw_unless(is_string($assetType), RuntimeException::class, 'Asset type must be a string.');

        return $assetType;
    }

    /**
     * @return class-string<Model>
     */
    private static function assetModelClass(AssetData $asset): string
    {
        throw_unless(is_subclass_of($asset->model, Model::class), RuntimeException::class, 'Asset model must be an Eloquent model class.');

        /** @var class-string<Model> $modelClass */
        $modelClass = $asset->model;

        return $modelClass;
    }

    private static function htmlableText(Htmlable|string|null $value): string
    {
        return $value instanceof Htmlable ? $value->toHtml() : (string) $value;
    }

    private static function modifyCreateAction(Action $action): Action
    {
        return $action->slideOver()
            ->modalWidth(Width::ScreenLarge)
            ->closeModalByClickingAway(false)
            ->successNotificationTitle(
                fn (Action $action): string => __(
                    'capell-admin::notification.created_successfully',
                    ['name' => self::htmlableText($action->getModalHeading())],
                ),
            )
            ->after(function (Action $action): void {
                $action->success();
            });
    }
}
