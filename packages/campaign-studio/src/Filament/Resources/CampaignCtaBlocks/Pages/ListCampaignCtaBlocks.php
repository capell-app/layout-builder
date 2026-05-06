<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Resources\CampaignCtaBlocks\Pages;

use Capell\CampaignStudio\Filament\Resources\CampaignCtaBlocks\CampaignCtaBlockResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCampaignCtaBlocks extends ListRecords
{
    protected static string $resource = CampaignCtaBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
