<?php

declare(strict_types=1);

namespace Capell\FilamentPeek\Filament\Extenders;

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Filament\Panel;
use Pboivin\FilamentPeek\FilamentPeekPlugin;

final class FilamentPeekAdminPanelExtender implements AdminPanelExtender
{
    public function extend(Panel $panel): void
    {
        if ($panel->hasPlugin(FilamentPeekPlugin::ID)) {
            return;
        }

        $panel->plugin(FilamentPeekPlugin::make());
    }
}
