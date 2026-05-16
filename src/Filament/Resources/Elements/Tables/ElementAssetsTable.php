<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Elements\Tables;

use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Core\Actions\GetEditPageResourceUrlAction;
use Capell\Core\Actions\ResolvePageableMorphModelAction;
use Capell\Core\Enums\BlueprintSubjectEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Filament\Components\Forms\AssetTypeSelect;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class ElementAssetsTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            /** @phpstan-ignore-next-line ElementAsset exposes this local scope through Eloquent. */
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withAssets())
            ->reorderable('order')
            ->heading(__('capell-layout-builder::heading.element_page_assets'))
            ->description(__('capell-layout-builder::generic.element_page_assets_description'))
            ->recordUrl(
                fn (ElementAsset $record): ?string => match ($record->asset_type) {
                    BlueprintSubjectEnum::Page->value => GetEditPageResourceUrlAction::run($record->asset),
                    default => AdminSurfaceLookup::resource(ucfirst($record->asset_type))::getUrl(
                        'edit',
                        ['record' => $record->asset],
                    ),
                },
            )
            ->columns(self::getTableColumns())
            ->filters(self::getTableFilters())
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    ReplicateAction::make(),
                    DeleteAction::make(),
                ])
                    ->color('gray'),
            ])
            ->headerActions([
                self::createResourcesAction(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            NameColumn::make('asset.name'),
            TextColumn::make('asset_type')
                ->label(__('capell-layout-builder::table.asset_type'))
                ->badge()
                ->sortable(),
            PageNameColumn::make('pageable.name')
                ->label(__('capell-admin::table.page'))
                ->withParents()
                ->sortable(),
            DateColumn::make('updated_at')
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            Filter::make('filter')
                ->columnSpanFull()
                ->schema([
                    Select::make('pages')
                        ->label(__('capell-admin::form.page'))
                        ->multiple()
                        ->options(
                            fn (HasTable $livewire): array => $livewire->getTable()->getQuery()
                                ->select(['pageable_type', 'pageable_id'])
                                ->withOnly('pageable')
                                ->whereNotNull(['pageable_type', 'pageable_id'])
                                ->groupBy(['pageable_type', 'pageable_id'])
                                ->get()
                                ->pluck(
                                    fn (ElementAsset $elementAsset): array => [self::buildLookupKey($elementAsset->pageable_type, $elementAsset->pageable_id) => $elementAsset->pageable instanceof Model ? $elementAsset->pageable->getAttribute('name') : null],
                                )
                                ->all(),
                        ),
                    AssetTypeSelect::make('type'),
                    Select::make('blueprint_id')
                        ->label(__('capell-admin::form.type'))
                        ->visibleJs(<<<'JS'
                             $get('type')
                        JS)
                        ->options(fn (Get $get): array => match ($get('type')) {
                            BlueprintSubjectEnum::Page->value => Page::getTypes(),
                            default => self::getAssetTypes((string) $get('type')),
                        }),
                ])
                ->query(
                    fn (Builder $query, array $data): Builder => $query
                        ->when(
                            isset($data['asset_type']) && filled($data['asset_type']),
                            fn (Builder $query): Builder => $query->where('asset_type', $data['asset_type']),
                        )
                        ->when(
                            isset($data['blueprint_id']) && filled($data['blueprint_id']),
                            fn (Builder $query): Builder => $query->where('blueprint_id', $data['blueprint_id']),
                        )
                        ->when(
                            isset($data['pages']) && filled($data['pages']),
                            fn (Builder $query): Builder => $query->where(function (Builder $query) use ($data): void {
                                $pageLookupKeys = is_array($data['pages']) ? $data['pages'] : [];

                                foreach ($pageLookupKeys as $pageLookupKey) {
                                    [$pageableType, $pageableId] = array_pad(explode(':', (string) $pageLookupKey, 2), 2, null);
                                    if (blank($pageableType)) {
                                        continue;
                                    }

                                    if (blank($pageableId)) {
                                        continue;
                                    }

                                    $query->orWhere(function (Builder $pageConditionQuery) use ($pageableType, $pageableId): void {
                                        $pageConditionQuery
                                            ->where('pageable_type', $pageableType)
                                            ->where('pageable_id', $pageableId);
                                    });
                                }
                            }),
                        ),
                )
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if (isset($data['asset_type'])) {
                        $indicators['asset_type'] = __(
                            'capell-layout-builder::filter.type',
                            ['type' => $data['asset_type']],
                        );
                    }

                    if (is_numeric($data['blueprint_id'] ?? null)) {
                        $blueprint = Blueprint::query()->find((int) $data['blueprint_id'], ['name']);

                        $indicators['blueprint_id'] = __(
                            'capell-layout-builder::filter.type',
                            ['search' => $blueprint?->name],
                        );
                    }

                    if (isset($data['pageable_type'], $data['pageable_id'])) {
                        $pageableModel = ResolvePageableMorphModelAction::run(
                            $data['pageable_type'],
                            $data['pageable_id'],
                            ['name'],
                        );

                        $pageableName = $pageableModel?->getAttribute('name');

                        if (is_string($pageableName) && filled($pageableName)) {
                            $indicators['page'] = __('capell-admin::filter.page', ['search' => $pageableName]);
                        }
                    }

                    return $indicators;
                }),
        ];
    }

    private static function createResourcesAction(): Action
    {
        return CreateAction::make()
            ->label(__('capell-layout-builder::button.add_asset'))
            ->color('primary')
            ->successNotificationTitle(__('capell-layout-builder::message.asset_added'))
            ->using(function (array $data, RelationManager $livewire): Model {
                $assetIds = $data['asset_id'] ?? null;
                $assetType = $data['asset_type'] ?? null;

                throw_if(! is_array($assetIds) || $assetIds === [], RuntimeException::class, 'No asset selected');
                throw_if(! is_string($assetType) || $assetType === '', RuntimeException::class, 'No asset type selected');

                $ownerRecord = $livewire->getOwnerRecord();

                throw_if(! $ownerRecord instanceof Element, RuntimeException::class, 'Element assets can only be attached to elements.');

                $createdAsset = null;

                foreach ($assetIds as $assetId) {
                    $createdAsset = $ownerRecord->assets()->create([
                        'asset_id' => $assetId,
                        'asset_type' => $assetType,
                        'related_type' => $ownerRecord->getMorphClass(),
                        'related_id' => $ownerRecord->getKey(),
                    ]);
                }

                throw_if(! $createdAsset instanceof Model, RuntimeException::class, 'No asset was created.');

                return $createdAsset;
            });
    }

    private static function buildLookupKey(string $pageableType, int $pageableId): string
    {
        return $pageableType . ':' . $pageableId;
    }

    private static function getAssetTypes(string $assetType): array
    {
        if (blank($assetType)) {
            return [];
        }

        $registeredType = ucfirst($assetType);

        if (! CapellCore::hasAsset($registeredType)) {
            return [];
        }

        $model = CapellCore::getAsset($registeredType)->model;

        if (! method_exists($model, 'getTypes')) {
            return [];
        }

        return $model::getTypes();
    }
}
