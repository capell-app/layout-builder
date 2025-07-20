<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\WidgetResource\RelationManagers;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Components\Tables\Columns\CuratorColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Concerns\HasRelationManagerBadge;
use Capell\Core\Actions\EditPageUrlAction;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Models\Page;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Filament\Components\Forms\AssetTypeToggleButtons;
use Capell\Layout\Filament\Concerns\HasAssetsRelationManager;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\WidgetAsset;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WidgetAssetsRelationManager extends RelationManager
{
    use HasAssetsRelationManager;
    use HasRelationManagerBadge;

    protected static string $relationship = 'widgetAssets';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-admin::tab.resources');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::getAssetForm());
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withAssets())
            ->heading(__('capell-admin::heading.widget_page_assets'))
            ->description(__('capell-admin::generic.widget_page_assets_description'))
            ->columns([
                NameColumn::make('asset.name'),
                TextColumn::make('asset_type')
                    ->badge()
                    ->sortable(),
                CuratorColumn::make('asset.image')
                    ->label(__('capell-admin::table.image'))
                    ->relationship('image')
                    ->extraHeaderAttributes(['style' => 'width:1%']),
                PageNameColumn::make('page.name')
                    ->label(__('capell-admin::table.page'))
                    ->withParents()
                    ->sortable(),
            ])
            ->recordUrl(
                fn (WidgetAsset $record): ?string => match ($record->asset_type) {
                    TypeEnum::Page->value => EditPageUrlAction::run($record->asset),
                    default => CapellAdmin::getResource(ucfirst($record->asset_type))::getUrl(
                        'edit',
                        ['record' => $record->asset]
                    ),
                }
            )
            ->filters([
                Filter::make('filter')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('page_id')
                            ->label(__('capell-admin::form.page'))
                            ->options(
                                fn (self $livewire): array => $livewire->getTable()->getQuery()
                                    ->select('page_id')
                                    ->withOnly('page')
                                    ->whereNotNull('page_id')
                                    ->groupBy('page_id')
                                    ->get()
                                    ->mapWithKeys(
                                        fn (WidgetAsset $widgetAsset) => [$widgetAsset->page_id => $widgetAsset->page->name]
                                    )
                                    ->toArray()
                            ),

                        AssetTypeToggleButtons::make('type')
                            ->reactive(),

                        Select::make('type_id')
                            ->label(__('capell-admin::form.type'))
                            ->visible(fn (Get $get): bool => ! empty($get('type')))
                            ->options(fn (Get $get): array => match ($get('type')) {
                                LayoutTypeEnum::Content->value => Content::getTypes(),
                                TypeEnum::Page->value => Page::getTypes(),
                                default => []
                            }),
                    ])
                    ->query(
                        fn (Builder $query, array $data) => $query
                            ->when(
                                ! empty($data['asset_type']),
                                fn (Builder $query) => $query->where('asset_type', $data['asset_type'])
                            )
                            ->when(
                                ! empty($data['type_id']),
                                fn (Builder $query) => $query->where('type_id', $data['type_id'])
                            )
                            ->when(
                                ! empty($data['page_id']),
                                fn (Builder $query) => $query->where('page_id', $data['page_id'])
                            ),
                    )
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (! empty($data['asset_type'])) {
                            $indicators['asset_type'] = __(
                                'capell-admin::filter.type',
                                ['type' => $data['asset_type']]
                            );
                        }

                        if (! empty($data['type_id'])) {
                            $indicators['type_id'] = __(
                                'capell-admin::filter.type',
                                ['search' => Type::find($data['type_id'])->name]
                            );
                        }

                        if (! empty($data['page_id'])) {
                            $indicators['page_id'] = __(
                                'capell-admin::filter.page',
                                ['search' => Page::query()->withDrafts()->find($data['page_id'])->name]
                            );
                        }

                        return $indicators;
                    }),
            ])
            ->headerActions([
                self::createResourcesAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
