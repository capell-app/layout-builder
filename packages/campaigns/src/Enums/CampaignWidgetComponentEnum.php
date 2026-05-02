<?php

declare(strict_types=1);

namespace Capell\Campaigns\Enums;

enum CampaignWidgetComponentEnum: string
{
    case CampaignHero = 'capell-campaigns::components.widget.campaign-hero';
    case CampaignCtaBlock = 'capell-campaigns::components.widget.campaign-cta-block';
    case CampaignLeadForm = 'capell-campaigns::components.widget.campaign-lead-form';
}
