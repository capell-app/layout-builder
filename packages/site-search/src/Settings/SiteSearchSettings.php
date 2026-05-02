<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Settings;

use Capell\Core\Contracts\SettingsContract;
use Capell\SiteSearch\Enums\SearchDriver;
use Capell\SiteSearch\Filament\Settings\SiteSearchSettingsSchema;
use Spatie\LaravelSettings\Settings;

final class SiteSearchSettings extends Settings implements SettingsContract
{
    public bool $enabled = true;

    public bool $show_header_search = true;

    public int $results_per_page = 10;

    public SearchDriver $driver = SearchDriver::Database;

    public bool $record_search_logs = true;

    public int $log_retention_days = 180;

    public bool $hash_visitor_data = true;

    public int $minimum_query_length = 2;

    public static function group(): string
    {
        return 'site_search';
    }

    public static function schema(): string
    {
        return SiteSearchSettingsSchema::class;
    }
}
