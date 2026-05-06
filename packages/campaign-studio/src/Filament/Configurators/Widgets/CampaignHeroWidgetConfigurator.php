<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Configurators\Widgets;

use Capell\LayoutBuilder\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Filament\FormBuilder\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;

final class CampaignHeroWidgetConfigurator extends DefaultWidgetConfigurator
{
    protected function detailsTab(): Tab
    {
        return Tab::make('campaign_hero')
            ->label(__('capell-campaign-studio::generic.campaign'))
            ->schema([
                TextInput::make('meta.eyebrow')
                    ->label('Eyebrow'),
                TextInput::make('meta.primary_button_text')
                    ->label(__('capell-layout-builder::form.primary_button_text')),
                TextInput::make('meta.primary_button_url')
                    ->label(__('capell-layout-builder::form.primary_button_url')),
                TextInput::make('meta.secondary_button_text')
                    ->label(__('capell-layout-builder::form.secondary_button_text')),
                TextInput::make('meta.secondary_button_url')
                    ->label(__('capell-layout-builder::form.secondary_button_url')),
                TextInput::make('meta.goal_key')
                    ->label(__('capell-campaign-studio::form.primary_goal')),
            ]);
    }
}
