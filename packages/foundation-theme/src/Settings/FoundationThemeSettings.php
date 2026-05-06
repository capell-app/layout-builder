<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Settings;

use Capell\Core\Contracts\SettingsContract;
use Capell\FoundationTheme\Filament\Settings\FoundationThemeSettingsSchema;
use Spatie\LaravelSettings\Settings;

class FoundationThemeSettings extends Settings implements SettingsContract
{
    public bool $enable_lazy_loading = true;

    public bool $minify_assets = true;

    public static function group(): string
    {
        return 'foundation_theme';
    }

    public static function schema(): string
    {
        return FoundationThemeSettingsSchema::class;
    }
}
