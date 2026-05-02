<?php

declare(strict_types=1);

namespace Capell\Navigation\Contracts;

use Capell\Core\Contracts\Pageable;

interface NavigationPageSyncer
{
    /**
     * Remove the given page from all navigation items.
     */
    public function removePageFromAllNavigations(Pageable $page): void;
}
