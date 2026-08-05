<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Widgets\Tables;

use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Core\Actions\GetEditPageResourceUrlAction;
use Capell\Core\Actions\GetResourceFromBlueprintAction;
use Capell\Core\Actions\ResolvePageableMorphModelAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\BlueprintSubjectEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Filament\Components\Forms\AssetTypeSelect;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Throwable;

class WidgetAssetsTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['asset', 'pageable']))
            ->reorderable('order')
            ->heading(__('capell-layout-builder::heading.widget_page_assets'))
            ->description(__('capell-layout-builder::generic.widget_page_assets_description'))
            ->recordUrl(
                self::recordUrl(...),
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

    /**
     * @return array<array-key, mixed>
     */
    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            NameColumn::make('asset.name'),
            TextColumn::make('asset_type')
                ->label(__('capell-layout-builder::table.asset_type'))
                ->badge()
                ->sortable(),
            TextColumn::make('usage_status')
                ->label(__('capell-layout-builder::table.usage'))
                ->badge()
                ->getStateUsing(fn (WidgetAsset $record): string => $record->asset instanceof Model
                    ? ($record->pageable instanceof Model
                        ? (string) __('capell-layout-builder::table.widget_asset_usage_used')
                        : (string) __('capell-layout-builder::table.widget_asset_usage_unscoped'))
                    : (string) __('capell-layout-builder::table.widget_asset_usage_broken'))
                ->color(fn (WidgetAsset $record): string => ! $record->asset instanceof Model
                    ? 'danger'
                    : ($record->pageable instanceof Model ? 'success' : 'warning'))
                ->toggleable(),
            PageNameColumn::make('pageable.name')
                ->label(__('capell-admin::table.page'))
                ->withParents()
                ->sortable(),
            DateColumn::make('updated_at')
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
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
                            function (HasTable $livewire): array {
                                $query = $livewire->getTable()->getQuery();

                                if ($query === null) {
                                    return [];
                                }

                                return $query->select(['pageable_type', 'pageable_id'])
                                    ->withOnly('pageable')
                                    ->whereNotNull(['pageable_type', 'pageable_id'])
                                    ->groupBy(['pageable_type', 'pageable_id'])
                                    ->get()
                                    ->mapWithKeys(function (Model $widgetAsset): array {
                                        throw_unless($widgetAsset instanceof WidgetAsset);

                                        if (! is_string($widgetAsset->pageable_type) || ! is_int($widgetAsset->pageable_id)) {
                                            return [];
                                        }

                                        return [
                                            self::buildLookupKey($widgetAsset->pageable_type, $widgetAsset->pageable_id) => $widgetAsset->pageable instanceof Model ? $widgetAsset->pageable->getAttribute('name') : null,
                                        ];
                                    })
                                    ->all();
                            },
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

            SelectFilter::make('integrity')
                ->label(__('capell-layout-builder::table.widget_asset_integrity'))
                ->options([
                    'broken_reference' => __('capell-layout-builder::table.widget_asset_integrity_broken_reference'),
                    'unscoped' => __('capell-layout-builder::table.widget_asset_integrity_unscoped'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return match ($data['value'] ?? null) {
                        'broken_reference' => $query->whereDoesntHaveMorph('asset', '*'),
                        'unscoped' => $query->whereHasMorph('asset', '*')
                            ->where(fn (Builder $query): Builder => $query
                                ->whereNull('pageable_type')
                                ->orWhereNull('pageable_id')),
                        default => $query,
                    };
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

                throw_if(! $ownerRecord instanceof Widget, RuntimeException::class, 'Widget assets can only be attached to widgets.');

                $createdAsset = null;

                foreach ($assetIds as $assetId) {
                    $createdAsset = $ownerRecord->assets()->create([
                        'asset_id' => $assetId,
                        'asset_type' => $assetType,
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

    private static function recordUrl(WidgetAsset $record): ?string
    {
        $asset = $record->asset;

        if (! $asset instanceof Model || ! $asset->exists) {
            return null;
        }

        if ($record->asset_type === BlueprintSubjectEnum::Page->value && $asset instanceof Pageable) {
            return self::pageRecordUrl($asset);
        }

        $assetType = $record->asset_type;

        if (! is_string($assetType) || $assetType === '') {
            return null;
        }

        $resource = AdminSurfaceLookup::resourceIfRegistered(ucfirst($assetType));

        if ($resource === null || ! self::canEdit($resource, $asset)) {
            return null;
        }

        try {
            return $resource::getUrl('edit', ['record' => $asset]);
        } catch (Throwable) {
            return null;
        }
    }

    private static function pageRecordUrl(Model&Pageable $page): ?string
    {
        try {
            $page->loadMissing('blueprint');
            $blueprint = $page->getRelation('blueprint');

            if (! $blueprint instanceof Blueprint) {
                return null;
            }

            $resource = GetResourceFromBlueprintAction::run($blueprint);

            if (! is_subclass_of($resource, Resource::class)) {
                return null;
            }

            return self::canEdit($resource, $page)
                ? GetEditPageResourceUrlAction::run($page)
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  class-string<\Filament\Resources\Resource>  $resource
     */
    private static function canEdit(string $resource, Model $record): bool
    {
        return auth()->check() && $resource::canEdit($record);
    }

    /**
     * @return array<array-key, mixed>
     */
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
