<?php

declare(strict_types=1);

namespace Capell\Workspaces\Facades;

use Capell\Workspaces\Support\WorkspacesManager;
use Illuminate\Support\Facades\Facade;

/**
 * @mixin WorkspacesManager
 */
class CapellWorkspaces extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WorkspacesManager::class;
    }
}
