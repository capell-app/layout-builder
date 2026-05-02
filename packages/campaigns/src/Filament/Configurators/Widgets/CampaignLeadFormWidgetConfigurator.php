<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Configurators\Widgets;

use Capell\Mosaic\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;

final class CampaignLeadFormWidgetConfigurator extends DefaultWidgetConfigurator
{
    protected function detailsTab(): Tab
    {
        return Tab::make('campaign_form')
            ->label(__('capell-campaigns::form.form'))
            ->schema([
                TextInput::make('meta.form_handle')
                    ->label(__('capell-campaigns::form.form')),
                TextInput::make('meta.goal_key')
                    ->label(__('capell-campaigns::form.primary_goal')),
            ]);
    }
}
