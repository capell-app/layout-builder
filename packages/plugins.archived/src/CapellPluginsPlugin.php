<?php

declare(strict_types=1);

namespace Capell\Plugins;

use Capell\Plugins\Filament\Pages\PluginsPage;
use Capell\Plugins\Filament\Resources\MarketplacePluginResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

final class CapellPluginsPlugin implements Plugin
{
    public static function make(): self
    {
        return new self;
    }

    public function getId(): string
    {
        return 'capell-plugins';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                PluginsPage::class,
            ])
            ->resources([
                MarketplacePluginResource::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        // Boot logic if needed
    }
}
