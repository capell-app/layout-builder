<?php

declare(strict_types=1);

namespace Capell\Plugins\Filament\Resources\MarketplacePlugin\Pages;

use Capell\Plugins\Filament\Resources\MarketplacePluginResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarketplacePlugin extends EditRecord
{
    protected static string $resource = MarketplacePluginResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
