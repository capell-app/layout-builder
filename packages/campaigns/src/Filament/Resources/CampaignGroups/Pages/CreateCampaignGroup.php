<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Resources\CampaignGroups\Pages;

use Capell\Campaigns\Filament\Resources\CampaignGroups\CampaignGroupResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCampaignGroup extends CreateRecord
{
    protected static string $resource = CampaignGroupResource::class;
}
