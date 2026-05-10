<?php

declare(strict_types=1);

namespace Capell\Notes\Providers;

use Capell\Core\Facades\CapellCore;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(NotesServiceProvider::$packageName);
    }
}
