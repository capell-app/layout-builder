<?php

declare(strict_types=1);

namespace Capell\Analytics\Settings;

use Capell\Analytics\Filament\Settings\AnalyticsSettingsSchema;
use Capell\Core\Contracts\SettingsContract;
use Spatie\LaravelSettings\Settings;

final class AnalyticsSettings extends Settings implements SettingsContract
{
    public bool $enabled = true;

    public bool $track_page_views = true;

    public bool $track_clicks = true;

    public bool $track_forms = false;

    public bool $automatic_click_tracking = true;

    public bool $require_consent_for_all_regions = false;

    public ?string $default_consent_region = null;

    public string $policy_version = '1.0';

    public int $retention_days = 365;

    public bool $hash_visitor_data = true;

    public string $hash_salt = 'capell-analytics';

    /** @var list<string> */
    public array $ignored_paths = ['/admin*', '/livewire*', '/capell/analytics*'];

    /** @var list<string> */
    public array $ignored_selectors = ['[data-capell-analytics-ignore]', '[wire\\:click]'];

    public string $route_prefix = 'capell/analytics';

    public static function group(): string
    {
        return 'analytics';
    }

    public static function schema(): string
    {
        return AnalyticsSettingsSchema::class;
    }
}
