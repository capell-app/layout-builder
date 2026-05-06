<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Resources\CampaignLandingPages\Tables;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class CampaignLandingPagesTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('headline')
                    ->label(__('capell-campaign-studio::form.headline'))
                    ->searchable(),
                TextColumn::make('campaignGroup.name')
                    ->label(__('capell-campaign-studio::form.campaign_group'))
                    ->sortable(),
                TextColumn::make('primaryGoal.name')
                    ->label(__('capell-campaign-studio::form.primary_goal')),
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
