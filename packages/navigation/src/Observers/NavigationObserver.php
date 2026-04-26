<?php

declare(strict_types=1);

namespace Capell\Navigation\Observers;

use Capell\Core\Actions\GenerateUniqueKeyAction;
use Capell\Core\Support\CapellCoreHelper;
use Capell\Navigation\Enums\NavigationCacheEnum;
use Capell\Navigation\Models\Navigation;

class NavigationObserver
{
    public function saving(Navigation $navigation): void
    {
        if ($navigation->key === null || $navigation->key === '') {
            $navigation->key = GenerateUniqueKeyAction::run($navigation);
        }
    }

    public function saved(Navigation $navigation): void
    {
        $this->clearCache();
    }

    public function deleted(Navigation $navigation): void
    {
        $this->clearCache();
    }

    public function restored(Navigation $navigation): void
    {
        $this->clearCache();
    }

    private function clearCache(): void
    {
        CapellCoreHelper::flushCache([NavigationCacheEnum::NavigationNames]);
    }
}
