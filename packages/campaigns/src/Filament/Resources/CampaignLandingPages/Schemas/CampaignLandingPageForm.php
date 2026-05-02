<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Resources\CampaignLandingPages\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class CampaignLandingPageForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        return $configurator
            ->columns(['default' => 1, 'lg' => 2])
            ->schema([
                Select::make('campaign_group_id')
                    ->label(__('capell-campaigns::form.campaign_group'))
                    ->relationship('campaignGroup', 'name')
                    ->required(),
                TextInput::make('page_id')
                    ->label(__('capell-campaigns::form.page'))
                    ->numeric()
                    ->required(),
                TextInput::make('headline')
                    ->label(__('capell-campaigns::form.headline')),
                Select::make('primary_goal_id')
                    ->label(__('capell-campaigns::form.primary_goal'))
                    ->relationship('primaryGoal', 'name'),
                TextInput::make('utm_content')
                    ->label(__('capell-campaigns::form.utm_content')),
                TextInput::make('utm_term')
                    ->label(__('capell-campaigns::form.utm_term')),
                Toggle::make('is_primary')
                    ->label(__('capell-campaigns::form.is_primary')),
            ]);
    }
}
