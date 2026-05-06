<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Resources\CampaignLandingPages\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Filament\FormBuilder\Components\Select;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Components\Toggle;
use Filament\Schemas\Schema;

final class CampaignLandingPageForm implements FormConfigurator
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
                TextInput::make('page_id')
                    ->label(__('capell-campaign-studio::form.page'))
                    ->numeric()
                    ->required(),
                TextInput::make('headline')
                    ->label(__('capell-campaign-studio::form.headline')),
                Select::make('primary_goal_id')
                    ->label(__('capell-campaign-studio::form.primary_goal'))
                    ->relationship('primaryGoal', 'name'),
                TextInput::make('utm_content')
                    ->label(__('capell-campaign-studio::form.utm_content')),
                TextInput::make('utm_term')
                    ->label(__('capell-campaign-studio::form.utm_term')),
                Toggle::make('is_primary')
                    ->label(__('capell-campaign-studio::form.is_primary')),
            ]);
    }
}
