<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\WidgetResource\RelationManagers;

use Capell\Admin\Filament\Components\Tables\Columns\CuratorColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Concerns\HasRelationManagerBadge;
use Capell\Admin\Filament\Resources\MediaResource;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Models;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Filament\Concerns\HasAssetsRelationManager;
use Capell\Layout\Filament\Resources\ContentResource;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\WidgetAsset;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
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

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema(static::getResourceableForm());
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withResourceables(withDrafts: true))
            ->heading(__('capell-admin::heading.widget_page_resources'))
            ->description(__('capell-admin::generic.widget_page_resources_description'))
            ->columns([
                NameColumn::make('asset.name'),
                Tables\Columns\TextColumn::make('asset_type')
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
            ->recordUrl(fn (WidgetAsset $record): string => match ($record->asset_type) {
                // TODO: Implement for other asset types
                LayoutTypeEnum::Content->value => ContentResource::getUrl('edit', ['record' => $record->asset]),
                TypeEnum::Media->value => MediaResource::getUrl('edit', ['record' => $record->asset]),
                TypeEnum::Page->value => $record->asset->edit_url,
            })
            ->filters([
                Tables\Filters\Filter::make('filter')
                    ->columnSpanFull()
                    ->form([
                        Forms\Components\Select::make('page_id')
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

                        Forms\Components\ToggleButtons::make('type')
                            ->label(__('capell-admin::form.type'))
                            ->reactive()
                            ->options(fn (): array => WidgetAsset::getTypes())
                            ->inline(),

                        Forms\Components\Select::make('type_id')
                            ->label(__('capell-admin::form.type'))
                            ->visible(fn (Forms\Get $get): bool => ! empty($get('type')))
                            ->options(fn (Forms\Get $get): array => match ($get('type')) {
                                LayoutTypeEnum::Content->value => Content::getTypes(),
                                TypeEnum::Page->value => Models\Page::getTypes(),
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
                                ['search' => Models\Type::find($data['type_id'])->name]
                            );
                        }

                        if (! empty($data['page_id'])) {
                            $indicators['page_id'] = __(
                                'capell-admin::filter.page',
                                ['search' => Models\Page::query()->withDrafts()->find($data['page_id'])->name]
                            );
                        }

                        return $indicators;
                    }),
            ])
            ->headerActions([
                self::createResourcesAction(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
