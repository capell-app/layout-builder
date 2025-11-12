<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Tables;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Core\Actions\GetEditPageResourceUrlAction;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Models\Page;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Filament\Components\Forms\AssetTypeSelect;
use Capell\Layout\Filament\Concerns\HasAssetsRelationManager;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\WidgetAsset;
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
            ->heading(__('capell-admin::heading.widget_page_assets'))
            ->description(__('capell-admin::generic.widget_page_assets_description'))
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
            PageNameColumn::make('page.name')
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
                    Select::make('page_id')
                        ->label(__('capell-admin::form.page'))
                        ->options(
                            fn (HasTable $livewire): array => $livewire->getTable()->getQuery()
                                ->select('page_id')
                                ->withOnly('page')
                                ->whereNotNull('page_id')
                                ->groupBy('page_id')
                                ->get()
                                ->mapWithKeys(
                                    fn (WidgetAsset $widgetAsset): array => [$widgetAsset->page_id => $widgetAsset->page->name],
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
                    fn (Builder $query, array $data) => $query
                        ->unless(
                            empty($data['asset_type']),
                            fn (Builder $query) => $query->where('asset_type', $data['asset_type']),
                        )
                        ->unless(
                            empty($data['type_id']),
                            fn (Builder $query) => $query->where('type_id', $data['type_id']),
                        )
                        ->unless(
                            empty($data['page_id']),
                            fn (Builder $query) => $query->where('page_id', $data['page_id']),
                        ),
                )
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if (! empty($data['asset_type'])) {
                        $indicators['asset_type'] = __(
                            'capell-admin::filter.type',
                            ['type' => $data['asset_type']],
                        );
                    }

                    if (! empty($data['type_id'])) {
                        $indicators['type_id'] = __(
                            'capell-admin::filter.type',
                            ['search' => Type::query()->find($data['type_id'])->name],
                        );
                    }

                    if (! empty($data['page_id'])) {
                        $indicators['page_id'] = __(
                            'capell-admin::filter.page',
                            ['search' => Page::query()->withDrafts()->find($data['page_id'])->name],
                        );
                    }

                    return $indicators;
                }),
        ];
    }
}
