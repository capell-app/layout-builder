<?php

declare(strict_types=1);

use Capell\Search\Actions\BuildTopSearchesQueryAction;
use Capell\Search\Actions\BuildTrendingSearchesQueryAction;
use Capell\Search\Actions\BuildZeroResultSearchesQueryAction;
use Capell\Search\Data\SearchInsightsWindowData;
use Capell\Search\Models\SearchLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    config()->set('capell-search.logs.table_name', 'search_logs');

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

test('top searches groups normalized queries by search count', function (): void {
    $window = siteSearchInsightsWindow();

    SearchLog::factory()->create([
        'query' => 'Laravel Search',
        'normalized_query' => 'laravel search',
        'results_count' => 5,
        'searched_at' => $window->start->addDay(),
    ]);
    SearchLog::factory()->create([
        'query' => 'laravel search',
        'normalized_query' => 'laravel search',
        'results_count' => 7,
        'searched_at' => $window->start->addDays(2),
    ]);
    SearchLog::factory()->create([
        'query' => 'Filament',
        'normalized_query' => 'filament',
        'results_count' => 3,
        'searched_at' => $window->start->addDays(3),
    ]);
    SearchLog::factory()->create([
        'query' => 'Outside',
        'normalized_query' => 'outside',
        'results_count' => 20,
        'searched_at' => $window->start->subDay(),
    ]);

    $summaries = BuildTopSearchesQueryAction::run($window);

    expect($summaries)->toHaveCount(2);
    expect($summaries[0]->normalizedQuery)->toBe('laravel search');
    expect($summaries[0]->searches)->toBe(2);
    expect($summaries[0]->resultsCount)->toBe(12);
    expect($summaries[1]->normalizedQuery)->toBe('filament');
});

test('zero result searches only includes searches with no results', function (): void {
    $window = siteSearchInsightsWindow();

    SearchLog::factory()->create([
        'query' => 'Missing page',
        'normalized_query' => 'missing page',
        'results_count' => 0,
        'searched_at' => $window->start->addDay(),
    ]);
    SearchLog::factory()->create([
        'query' => 'Found page',
        'normalized_query' => 'found page',
        'results_count' => 2,
        'searched_at' => $window->start->addDay(),
    ]);

    $summaries = BuildZeroResultSearchesQueryAction::run($window);

    expect($summaries)->toHaveCount(1);
    expect($summaries[0]->normalizedQuery)->toBe('missing page');
    expect($summaries[0]->resultsCount)->toBe(0);
});

test('trending searches compares against the previous equivalent window', function (): void {
    $window = siteSearchInsightsWindow();

    SearchLog::factory()->count(2)->create([
        'query' => 'Laravel',
        'normalized_query' => 'laravel',
        'results_count' => 4,
        'searched_at' => $window->start->subDays(2),
    ]);
    SearchLog::factory()->count(6)->create([
        'query' => 'Laravel',
        'normalized_query' => 'laravel',
        'results_count' => 4,
        'searched_at' => $window->start->addDays(2),
    ]);
    SearchLog::factory()->create([
        'query' => 'New search',
        'normalized_query' => 'new search',
        'results_count' => 1,
        'searched_at' => $window->start->addDays(3),
    ]);

    $summaries = BuildTrendingSearchesQueryAction::run($window);

    expect($summaries->pluck('normalizedQuery')->all())->toBe(['laravel', 'new search']);
    expect($summaries[0]->trendPercentage)->toBe(200.0);
    expect($summaries[1]->trendPercentage)->toBe(100.0);
});

function siteSearchInsightsWindow(): SearchInsightsWindowData
{
    return new SearchInsightsWindowData(
        start: CarbonImmutable::parse('2026-04-01 00:00:00'),
        end: CarbonImmutable::parse('2026-04-08 00:00:00'),
    );
}
