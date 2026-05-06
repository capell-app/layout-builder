<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Resources\CampaignLandingPages\Pages;

use Capell\CampaignStudio\Filament\Resources\CampaignLandingPages\CampaignLandingPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCampaignLandingPages extends ListRecords
{
    protected static string $resource = CampaignLandingPageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
