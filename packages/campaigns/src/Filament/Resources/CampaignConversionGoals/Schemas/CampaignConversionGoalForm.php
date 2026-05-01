<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Resources\CampaignConversionGoals\Schemas;

use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Campaigns\Enums\ConversionGoalType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class CampaignConversionGoalForm implements FormConfigurator
{
    public static function configure(Schema $configurator): Schema
    {
        return $configurator
            ->columns(['default' => 1, 'lg' => 2])
            ->schema([
                Select::make('campaign_group_id')
                    ->label(__('capell-campaigns::form.campaign_group'))
                    ->relationship('campaignGroup', 'name')
                    ->required(),
                TextInput::make('site_id')
                    ->label(__('capell-campaigns::form.site'))
                    ->numeric(),
                TextInput::make('name')
                    ->label(__('capell-campaigns::form.name'))
                    ->required(),
                TextInput::make('key')
                    ->label(__('capell-campaigns::form.key'))
                    ->required(),
                Select::make('type')
                    ->label(__('capell-campaigns::form.type'))
                    ->options(ConversionGoalType::class)
                    ->required(),
                TextInput::make('target')
                    ->label(__('capell-campaigns::form.target')),
                TextInput::make('value_amount')
                    ->label(__('capell-campaigns::form.value_amount'))
                    ->numeric(),
                Toggle::make('is_primary')
                    ->label(__('capell-campaigns::form.is_primary')),
                Toggle::make('is_active')
                    ->label(__('capell-campaigns::form.is_active')),
            ]);
    }
}
