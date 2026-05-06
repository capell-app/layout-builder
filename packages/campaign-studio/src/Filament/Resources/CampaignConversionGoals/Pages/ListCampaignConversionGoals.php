<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Resources\CampaignConversionGoals\Pages;

use Capell\CampaignStudio\Filament\Resources\CampaignConversionGoals\CampaignConversionGoalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCampaignConversionGoals extends ListRecords
{
    protected static string $resource = CampaignConversionGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
