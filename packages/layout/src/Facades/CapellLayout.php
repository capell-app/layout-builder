<?php

declare(strict_types=1);

namespace Capell\Layout\Facades;

use Capell\Layout\Support\CapellLayoutManager;
use Illuminate\Support\Facades\Facade;

/**
 * @mixin CapellLayoutManager
 */
class CapellLayout extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CapellLayoutManager::class;
    }
}
