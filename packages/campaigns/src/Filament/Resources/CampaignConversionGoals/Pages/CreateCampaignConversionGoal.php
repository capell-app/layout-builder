<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Resources\CampaignConversionGoals\Pages;

use Capell\Campaigns\Filament\Resources\CampaignConversionGoals\CampaignConversionGoalResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCampaignConversionGoal extends CreateRecord
{
    protected static string $resource = CampaignConversionGoalResource::class;
}
