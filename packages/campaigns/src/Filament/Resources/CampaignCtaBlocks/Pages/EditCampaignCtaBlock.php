<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Resources\CampaignCtaBlocks\Pages;

use Capell\Campaigns\Filament\Resources\CampaignCtaBlocks\CampaignCtaBlockResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditCampaignCtaBlock extends EditRecord
{
    protected static string $resource = CampaignCtaBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
