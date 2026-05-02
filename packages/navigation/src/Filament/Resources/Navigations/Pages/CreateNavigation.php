<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Resources\Navigations\Pages;

use Capell\Admin\Filament\Concerns\HasPageCacheNotification;
use Capell\Admin\Filament\Notifications\ClearCacheNotification;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Models\Navigation;
use Filament\Resources\Pages\CreateRecord;
use Override;

/**
 * @property-read Navigation $record
 */
class CreateNavigation extends CreateRecord
{
    use HasPageCacheNotification;

    /** @return class-string<NavigationResource> */
    #[Override]
    public static function getResource(): string
    {
        return NavigationResource::class;
    }

    #[Override]
    protected function getFormActions(): array
    {
        return [];
    }

    protected function afterCreate(): void
    {
        ClearCacheNotification::make()->send();
    }
}
