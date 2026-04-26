<?php

declare(strict_types=1);

namespace Capell\Navigation\Adapters;

use Capell\Core\Contracts\Pageable;
use Capell\Navigation\Actions\RemovePageFromNavigationAction;
use Capell\Navigation\Contracts\NavigationPageSyncer;
use Capell\Navigation\Models\Navigation;
use Illuminate\Support\Collection;

class NavigationPageSyncerAdapter implements NavigationPageSyncer
{
    public function removePageFromAllNavigations(Pageable $page): void
    {
        Navigation::query()->chunk(
            100,
            fn (Collection $navigations) => $navigations->each(
                fn (Navigation $navigation) => RemovePageFromNavigationAction::run($page, $navigation),
            ),
        );
    }
}
