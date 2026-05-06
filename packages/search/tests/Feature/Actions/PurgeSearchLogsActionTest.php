<?php

declare(strict_types=1);

use Capell\Search\Actions\PurgeSearchLogsAction;
use Capell\Search\Models\SearchLog;
use Capell\Search\Settings\SearchSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    app()->forgetInstance(SearchSettings::class);

    config()->set('capell-search.logs.table_name', 'search_logs');
    config()->set('capell-search.logs.retention_days', 30);

    Schema::dropIfExists('search_logs');
    Schema::create('search_logs', function (Blueprint $table): void {
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
    Schema::dropIfExists('search_logs');
});

test('purges search logs older than the resolved retention period', function (): void {
    SearchLog::factory()->create([
        'query' => 'expired',
        'normalized_query' => 'expired',
        'searched_at' => now()->subDays(31),
    ]);
    SearchLog::factory()->create([
        'query' => 'retained',
        'normalized_query' => 'retained',
        'searched_at' => now()->subDays(30),
    ]);

    $deletedCount = PurgeSearchLogsAction::run();

    expect($deletedCount)->toBe(1);
    expect(SearchLog::query()->pluck('normalized_query')->all())->toBe(['retained']);
});

test('uses site search settings before config retention', function (): void {
    $settings = new SearchSettings;
    $settings->log_retention_days = 10;

    app()->instance(SearchSettings::class, $settings);

    SearchLog::factory()->create([
        'query' => 'settings expired',
        'normalized_query' => 'settings expired',
        'searched_at' => now()->subDays(11),
    ]);
    SearchLog::factory()->create([
        'query' => 'config retained settings expired',
        'normalized_query' => 'config retained settings expired',
        'searched_at' => now()->subDays(20),
    ]);
    SearchLog::factory()->create([
        'query' => 'settings retained',
        'normalized_query' => 'settings retained',
        'searched_at' => now()->subDays(9),
    ]);

    $deletedCount = PurgeSearchLogsAction::run();

    expect($deletedCount)->toBe(2);
    expect(SearchLog::query()->pluck('normalized_query')->all())->toBe(['settings retained']);
});

test('allows callers to override retention days', function (): void {
    SearchLog::factory()->create([
        'query' => 'override expired',
        'normalized_query' => 'override expired',
        'searched_at' => now()->subDays(6),
    ]);
    SearchLog::factory()->create([
        'query' => 'override retained',
        'normalized_query' => 'override retained',
        'searched_at' => now()->subDays(4),
    ]);

    $deletedCount = PurgeSearchLogsAction::run(5);

    expect($deletedCount)->toBe(1);
    expect(SearchLog::query()->pluck('normalized_query')->all())->toBe(['override retained']);
});
