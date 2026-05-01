<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Resources\CampaignCtaBlocks\Tables;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class CampaignCtaBlocksTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('capell-campaigns::form.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('campaignGroup.name')
                    ->label(__('capell-campaigns::form.campaign_group')),
                TextColumn::make('key')
                    ->label(__('capell-campaigns::form.key')),
                TextColumn::make('is_active')
                    ->label(__('capell-campaigns::form.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
