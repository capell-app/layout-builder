<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Resources\Navigations\Pages;

use Capell\Admin\Filament\Actions\CreateAction;
use Capell\Admin\Filament\Concerns\HasPageCacheNotification;
use Capell\Admin\Filament\Concerns\HasSiteTableFilterTabs;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class ListNavigations extends ListRecords
{
    use HasPageCacheNotification;
    use HasSiteTableFilterTabs;

    protected string $siteRelation = 'navigations';

    /** @return class-string<NavigationResource> */
    #[Override]
    public static function getResource(): string
    {
        return NavigationResource::class;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-admin::generic.navigation_info');
    }

    protected function getActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver(),
        ];
    }

    protected function hasNoSitesFilterTab(): bool
    {
        return false;
    }
}
