<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Filament\Extenders\Page;

use Capell\Admin\Contracts\Extenders\ResourceHeaderActionExtender;
use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Admin\Filament\Resources\Pages\Pages\ListPages;
use Capell\SiteDiscovery\Filament\Pages\SitemapPage;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Route;

class SitemapResourceHeaderActionExtender implements ResourceHeaderActionExtender
{
    public function supports(string $pageClass): bool
    {
        return in_array($pageClass, [EditPage::class, ListPages::class], true);
    }

    /** @return array<int, Action> */
    public function actions(): array
    {
        return [
            Action::make('sitemap')
                ->label(__('capell-admin::button.sitemap'))
                ->icon('heroicon-c-globe-alt')
                ->color('gray')
                ->url(fn (): ?string => Route::has('filament.admin.pages.sitemap') ? SitemapPage::getUrl() : null)
                ->visible(fn (): bool => Route::has('filament.admin.pages.sitemap')),
        ];
    }
}
