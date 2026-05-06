<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Settings;

use Capell\Core\Contracts\SettingsContract;
use Capell\GA4Reports\Filament\Settings\GA4ReportsSettingsSchema;
use Spatie\LaravelSettings\Settings;

final class GA4ReportsSettings extends Settings implements SettingsContract
{
    public bool $enabled = false;

    public string $property_id = '';

    public string $credentials_path = '';

    public int $sync_days = 30;

    public string $route_slug = 'ga4-reports';

    public static function group(): string
    {
        return 'ga4_reports';
    }

    public static function schema(): string
    {
        return GA4ReportsSettingsSchema::class;
    }
}
