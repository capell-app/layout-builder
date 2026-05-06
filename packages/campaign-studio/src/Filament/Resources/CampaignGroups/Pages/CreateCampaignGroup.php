<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Resources\CampaignGroups\Pages;

use Capell\CampaignStudio\Filament\Resources\CampaignGroups\CampaignGroupResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCampaignGroup extends CreateRecord
{
    protected static string $resource = CampaignGroupResource::class;
}
