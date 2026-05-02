<?php

declare(strict_types=1);

namespace Capell\Campaigns\Enums;

use Capell\Campaigns\Filament\Resources\CampaignConversionGoals\CampaignConversionGoalResource;
use Capell\Campaigns\Filament\Resources\CampaignCtaBlocks\CampaignCtaBlockResource;
use Capell\Campaigns\Filament\Resources\CampaignGroups\CampaignGroupResource;
use Capell\Campaigns\Filament\Resources\CampaignLandingPages\CampaignLandingPageResource;

enum ResourceEnum: string
{
    case CampaignGroup = CampaignGroupResource::class;
    case CampaignLandingPage = CampaignLandingPageResource::class;
    case CampaignCtaBlock = CampaignCtaBlockResource::class;
    case CampaignConversionGoal = CampaignConversionGoalResource::class;
}
