<?php

declare(strict_types=1);

use Capell\SiteSearch\Contracts\SiteSearch;
use Capell\SiteSearch\Drivers\DatabaseSiteSearch;
use Capell\SiteSearch\Enums\SearchDriver;
use Capell\SiteSearch\Providers\SiteSearchServiceProvider;
use Capell\SiteSearch\Settings\SiteSearchSettings;

test('provider binds the configured database search driver', function (): void {
    app()->register(SiteSearchServiceProvider::class);
    config()->set('capell-site-search.driver', SearchDriver::Database->value);

    expect(resolve(SiteSearch::class))->toBeInstanceOf(DatabaseSiteSearch::class);
});

test('provider prefers the settings driver over config', function (): void {
    $settings = new SiteSearchSettings;
    $settings->driver = SearchDriver::Database;

    app()->instance(SiteSearchSettings::class, $settings);
    app()->register(SiteSearchServiceProvider::class);

    config()->set('capell-site-search.driver', SearchDriver::Scout->value);

    expect(resolve(SiteSearch::class))->toBeInstanceOf(DatabaseSiteSearch::class);
});

test('site search settings expose defaults', function (): void {
    expect(new SiteSearchSettings)
        ->enabled->toBeTrue()
        ->show_header_search->toBeTrue()
        ->results_per_page->toBe(10)
        ->driver->toBe(SearchDriver::Database)
        ->record_search_logs->toBeTrue()
        ->log_retention_days->toBe(180)
        ->hash_visitor_data->toBeTrue()
        ->minimum_query_length->toBe(2);
});
