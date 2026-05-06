<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Enums;

use Capell\CampaignStudio\Filament\Configurators\Widgets\CampaignCtaBlockWidgetConfigurator;
use Capell\CampaignStudio\Filament\Configurators\Widgets\CampaignHeroWidgetConfigurator;
use Capell\CampaignStudio\Filament\Configurators\Widgets\CampaignLeadFormWidgetConfigurator;

enum CampaignWidgetConfiguratorEnum: string
{
    case CampaignHero = CampaignHeroWidgetConfigurator::class;
    case CampaignCtaBlock = CampaignCtaBlockWidgetConfigurator::class;
    case CampaignLeadForm = CampaignLeadFormWidgetConfigurator::class;
}
