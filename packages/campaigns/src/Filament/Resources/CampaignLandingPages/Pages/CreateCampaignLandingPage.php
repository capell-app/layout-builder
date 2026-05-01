<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Resources\CampaignLandingPages\Pages;

use Capell\Campaigns\Filament\Resources\CampaignLandingPages\CampaignLandingPageResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCampaignLandingPage extends CreateRecord
{
    protected static string $resource = CampaignLandingPageResource::class;
}
