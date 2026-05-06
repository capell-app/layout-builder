<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Facades;

use Capell\PublishingStudio\Support\PublishingStudioManager;
use Illuminate\Support\Facades\Facade;

/**
 * @mixin PublishingStudioManager
 */
class CapellPublishingStudio extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PublishingStudioManager::class;
    }
}
