<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Resources\Countries\Tables;

use Capell\Address\Filament\Resources\Countries\Schemas\CountryForm;
use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\StatusIconColumn;
use Capell\Admin\Filament\Components\Tables\Filters\StatusFilter;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CountriesTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->recordActions([
                EditAction::make('edit'),
                ActionGroup::make([
                    ReplicateAction::make('replicate')
                        ->schema(fn (Schema $configurator): Schema => CountryForm::configure($configurator)),
                    DeleteAction::make('delete'),
                ])
                    ->color('gray'),
            ])
            ->filters([
                StatusFilter::make('status'),
                TrashedFilter::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make('delete'),
                RestoreBulkAction::make('restore'),
                ForceDeleteBulkAction::make('forceDelete'),
            ]);
    }

    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            NameColumn::make('name')
                ->defaultBadge(),
            TextColumn::make('iso2')
                ->label(__('capell-address::table.iso_code'))
                ->sortable()
                ->toggleable(),
            TextColumn::make('addresses_count')
                ->label(__('capell-address::table.total_addresses'))
                ->alignCenter()
                ->numeric()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            StatusIconColumn::make('status'),
            DateColumn::make('created_at'),
            DateColumn::make('updated_at'),
            DateColumn::make('deleted_at'),
        ];
    }
}
