<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migration->exists('foundation_theme.enable_lazy_loading')) {
            $this->migration->add('foundation_theme.enable_lazy_loading', true);
        }

        if (! $this->migration->exists('foundation_theme.minify_assets')) {
            $this->migration->add('foundation_theme.minify_assets', true);
        }
    }
};
