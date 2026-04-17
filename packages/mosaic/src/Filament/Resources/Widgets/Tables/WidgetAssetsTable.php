<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Widgets\Tables;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Core\Actions\GetEditPageResourceUrlAction;
use Capell\Core\Actions\ResolvePageableMorphModelAction;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Models\Page;
use Capell\Core\Models\Type;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use Capell\Mosaic\Filament\Components\Forms\AssetTypeSelect;
use Capell\Mosaic\Filament\Concerns\HasAssetsRelationManager;
use Capell\Mosaic\Models\Content;
use Capell\Mosaic\Models\WidgetAsset;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WidgetAssetsTable implements TableConfigurator
{
    use HasAssetsRelationManager;

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withAssets())
            ->reorderable('order')
            ->heading(__('capell-mosaic::heading.widget_page_assets'))
            ->description(__('capell-mosaic::generic.widget_page_assets_description'))
            ->recordUrl(
                fn (WidgetAsset $record): ?string => match ($record->asset_type) {
                    TypeEnum::Page->value => GetEditPageResourceUrlAction::run($record->asset),
                    default => CapellAdmin::getResource(ucfirst((string) $record->asset_type))::getUrl(
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
                ->badge()
                ->sortable(),
            PageNameColumn::make('pageable.name')
                ->label(__('capell-admin::table.page'))
                ->withParents()
                ->sortable(),
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
                                ->withOnly('page')
                                ->whereNotNull(['pageable_type', 'pageable_id'])
                                ->groupBy(['pageable_type', 'pageable_id'])
                                ->get()
                                ->pluck(
                                    fn (WidgetAsset $widgetAsset): array => [self::buildLookupKey($widgetAsset->pageable_type, $widgetAsset->pageable_id) => $widgetAsset->page->name],
                                )
                                ->all(),
                        ),
                    AssetTypeSelect::make('type'),
                    Select::make('type_id')
                        ->label(__('capell-admin::form.type'))
                        ->visibleJs(<<<'JS'
                             $get('type')
                        JS)
                        ->options(fn (Get $get): array => match ($get('type')) {
                            LayoutTypeEnum::Content->value => Content::getTypes(),
                            TypeEnum::Page->value => Page::getTypes(),
                            default => []
                        }),
                ])
                ->query(
                    fn (Builder $query, array $data): Builder => $query
                        ->when(
                            isset($data['asset_type']) && filled($data['asset_type']),
                            fn (Builder $query): Builder => $query->where('asset_type', $data['asset_type']),
                        )
                        ->when(
                            isset($data['type_id']) && filled($data['type_id']),
                            fn (Builder $query): Builder => $query->where('type_id', $data['type_id']),
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
                            'capell-mosaic::filter.type',
                            ['type' => $data['asset_type']],
                        );
                    }

                    if (isset($data['type_id'])) {
                        $indicators['type_id'] = __(
                            'capell-mosaic::filter.type',
                            ['search' => Type::query()->find($data['type_id'], ['name'])->name],
                        );
                    }

                    if (isset($data['pageable_type'], $data['pageable_id'])) {
                        $pageableModel = ResolvePageableMorphModelAction::run(
                            $data['pageable_type'],
                            $data['pageable_id'],
                            ['name'],
                        );

                        if ($pageableModel !== null && filled($pageableModel->name)) {
                            $indicators['page'] = __('capell-admin::filter.page', ['search' => $pageableModel->name]);
                        }
                    }

                    return $indicators;
                }),
        ];
    }

    private static function buildLookupKey(string $pageableType, int $pageableId): string
    {
        return $pageableType . ':' . $pageableId;
    }
}
