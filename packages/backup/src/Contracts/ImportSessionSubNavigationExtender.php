<?php

declare(strict_types=1);

namespace Capell\Backup\Contracts;

use Filament\Navigation\NavigationItem;

interface ImportSessionSubNavigationExtender
{
    /** @var string */
    public const TAG = 'capell-admin:import-session-sub-navigation-extender';

    /** @return array<int, NavigationItem> */
    public function getItems(): array;
}
