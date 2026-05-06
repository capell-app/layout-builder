<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Resources\CampaignConversionGoals\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\CampaignStudio\Enums\ConversionGoalType;
use Filament\FormBuilder\Components\Select;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Components\Toggle;
use Filament\Schemas\Schema;

final class CampaignConversionGoalForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        return $configurator
            ->columns(['default' => 1, 'lg' => 2])
            ->schema([
                Select::make('campaign_group_id')
                    ->label(__('capell-campaign-studio::form.campaign_group'))
                    ->relationship('campaignGroup', 'name')
                    ->required(),
                TextInput::make('site_id')
                    ->label(__('capell-campaign-studio::form.site'))
                    ->numeric(),
                TextInput::make('name')
                    ->label(__('capell-campaign-studio::form.name'))
                    ->required(),
                TextInput::make('key')
                    ->label(__('capell-campaign-studio::form.key'))
                    ->required(),
                Select::make('type')
                    ->label(__('capell-campaign-studio::form.type'))
                    ->options(ConversionGoalType::class)
                    ->required(),
                TextInput::make('target')
                    ->label(__('capell-campaign-studio::form.target')),
                TextInput::make('value_amount')
                    ->label(__('capell-campaign-studio::form.value_amount'))
                    ->numeric(),
                Toggle::make('is_primary')
                    ->label(__('capell-campaign-studio::form.is_primary')),
                Toggle::make('is_active')
                    ->label(__('capell-campaign-studio::form.is_active')),
            ]);
    }
}
