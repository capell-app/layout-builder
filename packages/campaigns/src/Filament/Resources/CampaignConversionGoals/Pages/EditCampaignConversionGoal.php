<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Resources\CampaignConversionGoals\Pages;

use Capell\Campaigns\Filament\Resources\CampaignConversionGoals\CampaignConversionGoalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditCampaignConversionGoal extends EditRecord
{
    protected static string $resource = CampaignConversionGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
