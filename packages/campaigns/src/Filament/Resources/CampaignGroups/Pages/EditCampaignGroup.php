<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Resources\CampaignGroups\Pages;

use Capell\Campaigns\Filament\Resources\CampaignGroups\CampaignGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditCampaignGroup extends EditRecord
{
    protected static string $resource = CampaignGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
