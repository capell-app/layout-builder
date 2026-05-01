<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Resources\CampaignGroups\Pages;

use Capell\Campaigns\Filament\Resources\CampaignGroups\CampaignGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCampaignGroups extends ListRecords
{
    protected static string $resource = CampaignGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
