<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Configurators\Widgets;

use Capell\Mosaic\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;

final class CampaignCtaBlockWidgetConfigurator extends DefaultWidgetConfigurator
{
    protected function detailsTab(): Tab
    {
        return Tab::make('campaign_cta')
            ->label(__('capell-campaigns::generic.cta_block'))
            ->schema([
                TextInput::make('meta.cta_block_id')
                    ->label(__('capell-campaigns::form.cta_block'))
                    ->numeric(),
            ]);
    }
}
