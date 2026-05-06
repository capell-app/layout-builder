<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Configurators\Widgets;

use Capell\LayoutBuilder\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Filament\FormBuilder\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;

final class CampaignLeadFormWidgetConfigurator extends DefaultWidgetConfigurator
{
    protected function detailsTab(): Tab
    {
        return Tab::make('campaign_form')
            ->label(__('capell-campaign-studio::form.form'))
            ->schema([
                TextInput::make('meta.form_handle')
                    ->label(__('capell-campaign-studio::form.form')),
                TextInput::make('meta.goal_key')
                    ->label(__('capell-campaign-studio::form.primary_goal')),
            ]);
    }
}
