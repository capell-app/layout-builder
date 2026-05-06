<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Resources\CampaignCtaBlocks\Tables;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Filament\Actions\ActionGroup;
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
                    ->label(__('capell-campaign-studio::form.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('campaignGroup.name')
                    ->label(__('capell-campaign-studio::form.campaign_group')),
                TextColumn::make('key')
                    ->label(__('capell-campaign-studio::form.key')),
                TextColumn::make('is_active')
                    ->label(__('capell-campaign-studio::form.is_active')),
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
