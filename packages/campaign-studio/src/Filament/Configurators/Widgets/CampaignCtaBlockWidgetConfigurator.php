<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Configurators\Widgets;

use Capell\LayoutBuilder\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Filament\FormBuilder\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;

final class CampaignCtaBlockWidgetConfigurator extends DefaultWidgetConfigurator
{
    protected function detailsTab(): Tab
    {
        return Tab::make('campaign_cta')
            ->label(__('capell-campaign-studio::generic.cta_block'))
            ->schema([
                TextInput::make('meta.cta_block_id')
                    ->label(__('capell-campaign-studio::form.cta_block'))
                    ->numeric(),
            ]);
    }
}
