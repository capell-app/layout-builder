<?php

declare(strict_types=1);

namespace Capell\PasswordSecurity\Filament\Extenders;

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\PasswordSecurity\Http\Middleware\EnsurePasswordSecurityCompliance;
use Filament\Panel;

class PasswordSecurityPanelExtender implements AdminPanelExtender
{
    public function extend(Panel $panel): void
    {
        $panel->authMiddleware([
            EnsurePasswordSecurityCompliance::class,
        ]);
    }
}
