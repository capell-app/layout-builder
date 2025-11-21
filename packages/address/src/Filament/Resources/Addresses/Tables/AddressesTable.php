<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Resources\Addresses\Tables;

use Capell\Address\Filament\Resources\Addresses\Schemas\AddressForm;
use Capell\Address\Models\Address;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AddressesTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('country'))
            ->columns(static::getTableColumns())
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    ReplicateAction::make()
                        ->schema(fn (Schema $schema): Schema => AddressForm::configure($schema)),
                    DeleteAction::make(),
                ])
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('country_id')
                    ->relationship('country', 'name')
                    ->label('Country'),
                StatusFilter::make('status'),
                TrashedFilter::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ])
            ->reorderable('order');
    }

    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            NameColumn::make('name')
                ->defaultBadge(),
            TextColumn::make('address')
                ->getStateUsing(fn (Address $record): ?string => $record->full_address)
                ->searchable([
                    'line1',
                    'line2',
                    'city',
                    'state',
                    'postal_code',
                ])
                ->wrap(),
            StatusIconColumn::make('status'),
            DateColumn::make('created_at'),
            DateColumn::make('updated_at'),
            DateColumn::make('deleted_at'),
        ];
    }
}
