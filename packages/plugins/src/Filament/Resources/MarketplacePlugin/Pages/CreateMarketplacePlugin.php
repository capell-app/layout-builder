<?php

declare(strict_types=1);

namespace Capell\Plugins\Filament\Resources\MarketplacePlugin\Pages;

use Capell\Plugins\Filament\Resources\MarketplacePluginResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMarketplacePlugin extends CreateRecord
{
    protected static string $resource = MarketplacePluginResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}
