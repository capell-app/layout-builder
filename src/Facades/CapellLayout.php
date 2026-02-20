<?php

declare(strict_types=1);

namespace Capell\Layout\Facades;

use Capell\Layout\Models\Widget;
use Capell\Layout\Support\CapellLayoutManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void storeContainerWidget(string $containerKey, string $widgetKey, Widget $widget, int $occurrence = 1)
 * @method static Widget|null getContainerWidget(string $containerKey, string $widgetKey, int $occurrence = 1)
 * @method static Collection getContainerWidgets(string $containerKey)
 * @method static void clearContainerWidgets()
 *
 * @see Capell\Layout\Support\CapellLayoutManager
 */
class CapellLayout extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CapellLayoutManager::class;
    }
}
