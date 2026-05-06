<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Enums;

use Capell\CampaignStudio\Filament\Resources\CampaignConversionGoals\CampaignConversionGoalResource;
use Capell\CampaignStudio\Filament\Resources\CampaignCtaBlocks\CampaignCtaBlockResource;
use Capell\CampaignStudio\Filament\Resources\CampaignGroups\CampaignGroupResource;
use Capell\CampaignStudio\Filament\Resources\CampaignLandingPages\CampaignLandingPageResource;

enum ResourceEnum: string
{
    case CampaignGroup = CampaignGroupResource::class;
    case CampaignLandingPage = CampaignLandingPageResource::class;
    case CampaignCtaBlock = CampaignCtaBlockResource::class;
    case CampaignConversionGoal = CampaignConversionGoalResource::class;
}
