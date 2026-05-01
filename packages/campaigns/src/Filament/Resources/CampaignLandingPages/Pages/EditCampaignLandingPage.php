<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Resources\CampaignLandingPages\Pages;

use Capell\Campaigns\Filament\Resources\CampaignLandingPages\CampaignLandingPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditCampaignLandingPage extends EditRecord
{
    protected static string $resource = CampaignLandingPageResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
