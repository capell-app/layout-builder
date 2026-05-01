<?php

declare(strict_types=1);

namespace Capell\Campaigns\Enums;

use Capell\Campaigns\Filament\Configurators\Widgets\CampaignCtaBlockWidgetConfigurator;
use Capell\Campaigns\Filament\Configurators\Widgets\CampaignHeroWidgetConfigurator;
use Capell\Campaigns\Filament\Configurators\Widgets\CampaignLeadFormWidgetConfigurator;

enum CampaignWidgetConfiguratorEnum: string
{
    case CampaignHero = CampaignHeroWidgetConfigurator::class;
    case CampaignCtaBlock = CampaignCtaBlockWidgetConfigurator::class;
    case CampaignLeadForm = CampaignLeadFormWidgetConfigurator::class;
}
