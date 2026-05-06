<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Resources\CampaignGroups\Tables;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class CampaignGroupsTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('capell-campaign-studio::form.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('capell-campaign-studio::form.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label(__('capell-campaign-studio::form.starts_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label(__('capell-campaign-studio::form.ends_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('conversions_count')
                    ->label(__('capell-campaign-studio::generic.conversions'))
                    ->counts('conversions'),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    DeleteAction::make(),
                ])
                    ->color('gray'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
