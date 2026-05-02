<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Filament\Extenders;

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\AuthenticationLog\Http\Middleware\AdminActivityMiddleware;
use Filament\Panel;
use Tapp\FilamentAuthenticationLog\FilamentAuthenticationLogPlugin;

final class AuthenticationLogAdminPanelExtender implements AdminPanelExtender
{
    public function extend(Panel $panel): void
    {
        if (! $panel->hasPlugin('authentication-log')) {
            $panel->plugin(FilamentAuthenticationLogPlugin::make());
        }

        $panel->middleware([AdminActivityMiddleware::class], isPersistent: true);
    }
}
