<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Filament\Extenders\Site;

use Capell\Admin\Contracts\Extenders\SiteRecordActionExtender;
use Capell\Core\Models\Site;
use Capell\SiteDiscovery\Filament\Pages\SitemapPage;
use Filament\Actions\Action;

class SitemapSiteRecordActionExtender implements SiteRecordActionExtender
{
    /** @return array<int, Action> */
    public function actions(): array
    {
        return [
            Action::make('sitemap')
                ->label(__('capell-admin::button.sitemap'))
                ->icon('heroicon-o-globe-alt')
                ->color('info')
                ->url(fn (Site $record): string => SitemapPage::getUrl(['site_id' => $record->id])),
        ];
    }
}
