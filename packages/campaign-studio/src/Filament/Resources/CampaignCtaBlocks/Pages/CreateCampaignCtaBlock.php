<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Resources\CampaignCtaBlocks\Pages;

use Capell\CampaignStudio\Filament\Resources\CampaignCtaBlocks\CampaignCtaBlockResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCampaignCtaBlock extends CreateRecord
{
    protected static string $resource = CampaignCtaBlockResource::class;
}
