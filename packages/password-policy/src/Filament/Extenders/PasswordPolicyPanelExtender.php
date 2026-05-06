<?php

declare(strict_types=1);

namespace Capell\PasswordPolicy\Filament\Extenders;

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\PasswordPolicy\Http\Middleware\EnsurePasswordPolicyCompliance;
use Filament\Panel;

class PasswordPolicyPanelExtender implements AdminPanelExtender
{
    public function extend(Panel $panel): void
    {
        $panel->authMiddleware([
            EnsurePasswordPolicyCompliance::class,
        ]);
    }
}
