<?php

declare(strict_types=1);

namespace Capell\DefaultTheme\Settings;

use Capell\Core\Contracts\SettingsContract;
use Capell\DefaultTheme\Filament\Settings\DefaultThemeSettingsSchema;
use Spatie\LaravelSettings\Settings;

class DefaultThemeSettings extends Settings implements SettingsContract
{
    public bool $enable_lazy_loading = true;

    public bool $minify_assets = true;

    public static function group(): string
    {
        return 'default_theme';
    }

    public static function schema(): string
    {
        return DefaultThemeSettingsSchema::class;
    }
}
