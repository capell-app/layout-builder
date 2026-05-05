<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Settings;

use Capell\Core\Contracts\SettingsContract;
use Capell\GoogleAnalytics\Filament\Settings\GoogleAnalyticsSettingsSchema;
use Spatie\LaravelSettings\Settings;

final class GoogleAnalyticsSettings extends Settings implements SettingsContract
{
    public bool $enabled = false;

    public string $property_id = '';

    public string $credentials_path = '';

    public int $sync_days = 30;

    public string $route_slug = 'google-analytics';

    public static function group(): string
    {
        return 'google_analytics';
    }

    public static function schema(): string
    {
        return GoogleAnalyticsSettingsSchema::class;
    }
}
