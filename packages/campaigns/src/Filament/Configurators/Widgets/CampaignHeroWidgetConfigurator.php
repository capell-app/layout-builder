<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Configurators\Widgets;

use Capell\Mosaic\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;

final class CampaignHeroWidgetConfigurator extends DefaultWidgetConfigurator
{
    protected function detailsTab(): Tab
    {
        return Tab::make('campaign_hero')
            ->label(__('capell-campaigns::generic.campaign'))
            ->schema([
                TextInput::make('meta.eyebrow')
                    ->label('Eyebrow'),
                TextInput::make('meta.primary_button_text')
                    ->label(__('capell-mosaic::form.primary_button_text')),
                TextInput::make('meta.primary_button_url')
                    ->label(__('capell-mosaic::form.primary_button_url')),
                TextInput::make('meta.secondary_button_text')
                    ->label(__('capell-mosaic::form.secondary_button_text')),
                TextInput::make('meta.secondary_button_url')
                    ->label(__('capell-mosaic::form.secondary_button_url')),
                TextInput::make('meta.goal_key')
                    ->label(__('capell-campaigns::form.primary_goal')),
            ]);
    }
}
