<?php

declare(strict_types=1);

use Capell\SiteSearch\Filament\Settings\Contributors\SiteSearchDashboardSettingsContributor;
use Capell\SiteSearch\Filament\Widgets\SearchOverviewStatsWidget;
use Capell\SiteSearch\Filament\Widgets\TopSearchesWidget;
use Capell\SiteSearch\Filament\Widgets\TrendingSearchesWidget;
use Capell\SiteSearch\Filament\Widgets\ZeroResultSearchesWidget;
use Capell\SiteSearch\Models\SiteSearchLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function (): void {
    config()->set('capell-site-search.logs.table_name', 'site_search_logs');

    Schema::dropIfExists('site_search_logs');
    Schema::create('site_search_logs', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('site_id')->nullable()->index();
        $table->foreignId('language_id')->nullable()->index();
        $table->string('query');
        $table->string('normalized_query')->index();
        $table->unsignedInteger('results_count')->default(0);
        $table->string('clicked_result_url')->nullable();
        $table->string('ip_hash', 64)->nullable();
        $table->string('user_agent_hash', 64)->nullable();
        $table->timestamp('searched_at')->index();
        $table->timestamps();
    });

    SiteSearchLog::factory()->create([
        'query' => 'Laravel',
        'normalized_query' => 'laravel',
        'results_count' => 5,
        'searched_at' => now()->subDay(),
    ]);
    SiteSearchLog::factory()->create([
        'query' => 'Missing page',
        'normalized_query' => 'missing page',
        'results_count' => 0,
        'searched_at' => now()->subDay(),
    ]);
});

afterEach(function (): void {
    Schema::dropIfExists('site_search_logs');
});

test('dashboard settings contributor exposes site search widget keys', function (): void {
    $keys = collect((new SiteSearchDashboardSettingsContributor)->settingsKeys())->pluck('key')->all();

    expect($keys)->toBe([
        'site_search_overview',
        'top_searches',
        'trending_searches',
        'zero_result_searches',
    ]);
});

test('site search dashboard widgets render', function (string $widgetClass): void {
    Livewire::test($widgetClass)->assertOk();
})->with([
    SearchOverviewStatsWidget::class,
    TopSearchesWidget::class,
    TrendingSearchesWidget::class,
    ZeroResultSearchesWidget::class,
]);
