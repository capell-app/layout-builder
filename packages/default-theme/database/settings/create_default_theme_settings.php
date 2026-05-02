<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('default_theme.enable_lazy_loading')) {
            $this->migrator->add('default_theme.enable_lazy_loading', true);
        }

        if (! $this->migrator->exists('default_theme.minify_assets')) {
            $this->migrator->add('default_theme.minify_assets', true);
        }
    }
};
