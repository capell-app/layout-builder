<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Enums;

enum CampaignWidgetComponentEnum: string
{
    case CampaignHero = 'capell-campaign-studio::components.widget.campaign-hero';
    case CampaignCtaBlock = 'capell-campaign-studio::components.widget.campaign-cta-block';
    case CampaignLeadForm = 'capell-campaign-studio::components.widget.campaign-lead-form';
}
