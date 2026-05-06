<?php

declare(strict_types=1);

namespace Capell\Insights\Settings;

use Capell\Core\Contracts\SettingsContract;
use Capell\Insights\Filament\Settings\InsightsSettingsSchema;
use Spatie\LaravelSettings\Settings;

final class InsightsSettings extends Settings implements SettingsContract
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

    public string $hash_salt = 'capell-insights';

    /** @var list<string> */
    public array $ignored_paths = ['/admin*', '/livewire*', '/capell/insights*', '/_debugbar*', '/_clockwork*', '/storage*'];

    /** @var list<string> */
    public array $ignored_selectors = ['[data-capell-insights-ignore]', '[wire\\:click]'];

    public string $route_prefix = 'capell/insights';

    public static function group(): string
    {
        return 'insights';
    }

    public static function schema(): string
    {
        return InsightsSettingsSchema::class;
    }
}
