<?php

declare(strict_types=1);

namespace Capell\Mosaic\Facades;

use Capell\Mosaic\Support\CapellLayoutManager;
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
