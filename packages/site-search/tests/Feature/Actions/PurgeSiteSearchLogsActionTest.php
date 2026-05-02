<?php

declare(strict_types=1);

use Capell\SiteSearch\Actions\PurgeSiteSearchLogsAction;
use Capell\SiteSearch\Models\SiteSearchLog;
use Capell\SiteSearch\Settings\SiteSearchSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    app()->forgetInstance(SiteSearchSettings::class);

    config()->set('capell-site-search.logs.table_name', 'site_search_logs');
    config()->set('capell-site-search.logs.retention_days', 30);

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
});

afterEach(function (): void {
    Schema::dropIfExists('site_search_logs');
});

test('purges search logs older than the resolved retention period', function (): void {
    SiteSearchLog::factory()->create([
        'query' => 'expired',
        'normalized_query' => 'expired',
        'searched_at' => now()->subDays(31),
    ]);
    SiteSearchLog::factory()->create([
        'query' => 'retained',
        'normalized_query' => 'retained',
        'searched_at' => now()->subDays(30),
    ]);

    $deletedCount = PurgeSiteSearchLogsAction::run();

    expect($deletedCount)->toBe(1);
    expect(SiteSearchLog::query()->pluck('normalized_query')->all())->toBe(['retained']);
});

test('uses site search settings before config retention', function (): void {
    $settings = new SiteSearchSettings;
    $settings->log_retention_days = 10;

    app()->instance(SiteSearchSettings::class, $settings);

    SiteSearchLog::factory()->create([
        'query' => 'settings expired',
        'normalized_query' => 'settings expired',
        'searched_at' => now()->subDays(11),
    ]);
    SiteSearchLog::factory()->create([
        'query' => 'config retained settings expired',
        'normalized_query' => 'config retained settings expired',
        'searched_at' => now()->subDays(20),
    ]);
    SiteSearchLog::factory()->create([
        'query' => 'settings retained',
        'normalized_query' => 'settings retained',
        'searched_at' => now()->subDays(9),
    ]);

    $deletedCount = PurgeSiteSearchLogsAction::run();

    expect($deletedCount)->toBe(2);
    expect(SiteSearchLog::query()->pluck('normalized_query')->all())->toBe(['settings retained']);
});

test('allows callers to override retention days', function (): void {
    SiteSearchLog::factory()->create([
        'query' => 'override expired',
        'normalized_query' => 'override expired',
        'searched_at' => now()->subDays(6),
    ]);
    SiteSearchLog::factory()->create([
        'query' => 'override retained',
        'normalized_query' => 'override retained',
        'searched_at' => now()->subDays(4),
    ]);

    $deletedCount = PurgeSiteSearchLogsAction::run(5);

    expect($deletedCount)->toBe(1);
    expect(SiteSearchLog::query()->pluck('normalized_query')->all())->toBe(['override retained']);
});
