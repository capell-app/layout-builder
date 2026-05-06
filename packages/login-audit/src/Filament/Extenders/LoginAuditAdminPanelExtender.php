<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Filament\Extenders;

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\LoginAudit\Http\Middleware\AdminActivityMiddleware;
use Filament\Panel;
use Tapp\FilamentLoginAudit\FilamentLoginAuditPlugin;

final class LoginAuditAdminPanelExtender implements AdminPanelExtender
{
    public function extend(Panel $panel): void
    {
        if (! $panel->hasPlugin('login-audit')) {
            $panel->plugin(FilamentLoginAuditPlugin::make());
        }

        $panel->middleware([AdminActivityMiddleware::class], isPersistent: true);
    }
}
