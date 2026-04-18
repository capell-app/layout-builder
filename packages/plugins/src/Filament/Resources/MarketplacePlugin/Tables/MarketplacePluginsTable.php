<?php

declare(strict_types=1);

namespace Capell\Plugins\Filament\Resources\MarketplacePlugin\Tables;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MarketplacePluginsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendor')
                    ->label(__('Vendor'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kind')
                    ->label(__('Kind'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('license_model')
                    ->label(__('License Model'))
                    ->badge()
                    ->sortable(),
                BadgeColumn::make('is_visible')
                    ->label(__('Visible'))
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
