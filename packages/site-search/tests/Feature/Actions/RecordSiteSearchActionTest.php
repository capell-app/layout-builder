<?php

declare(strict_types=1);

use Capell\SiteSearch\Actions\RecordSearchResultClickAction;
use Capell\SiteSearch\Actions\RecordSiteSearchAction;
use Capell\SiteSearch\Data\SearchRequestData;
use Capell\SiteSearch\Models\SiteSearchLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    config()->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
    config()->set('capell-site-search.logs.table_name', 'site_search_logs');
    config()->set('capell-site-search.minimum_query_length', 2);
    config()->set('capell-site-search.record_search_logs', true);
    config()->set('capell-site-search.hash_visitor_data', true);

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

test('logs valid searches with normalized query and hashed visitor data', function (): void {
    $request = Request::create('/search', Symfony\Component\HttpFoundation\Request::METHOD_GET, server: [
        'REMOTE_ADDR' => '203.0.113.10',
        'HTTP_USER_AGENT' => 'Capell Test Browser',
    ]);

    $log = RecordSiteSearchAction::run(
        new SearchRequestData(
            query: '  Laravel   Search  ',
            siteId: 10,
            languageId: 20,
        ),
        7,
        $request,
    );

    expect($log)->toBeInstanceOf(SiteSearchLog::class);
    expect($log->site_id)->toBe(10);
    expect($log->language_id)->toBe(20);
    expect($log->query)->toBe('  Laravel   Search  ');
    expect($log->normalized_query)->toBe('laravel search');
    expect($log->results_count)->toBe(7);
    expect($log->ip_hash)->toBe(hash('sha256', '203.0.113.10|' . config('app.key')));
    expect($log->user_agent_hash)->toBe(hash('sha256', 'Capell Test Browser|' . config('app.key')));
    expect($log->searched_at)->toBeInstanceOf(CarbonImmutable::class);
});

test('skips blank searches', function (): void {
    $log = RecordSiteSearchAction::run(
        new SearchRequestData(query: '   '),
        0,
        Request::create('/search'),
    );

    expect($log)->toBeNull();
    expect(SiteSearchLog::query()->count())->toBe(0);
});

test('skips searches shorter than the minimum query length', function (): void {
    config()->set('capell-site-search.minimum_query_length', 3);

    $log = RecordSiteSearchAction::run(
        new SearchRequestData(query: 'ab'),
        0,
        Request::create('/search'),
    );

    expect($log)->toBeNull();
    expect(SiteSearchLog::query()->count())->toBe(0);
});

test('respects disabled search logging', function (): void {
    config()->set('capell-site-search.record_search_logs', false);

    $log = RecordSiteSearchAction::run(
        new SearchRequestData(query: 'Laravel Search'),
        1,
        Request::create('/search'),
    );

    expect($log)->toBeNull();
    expect(SiteSearchLog::query()->count())->toBe(0);
});

test('omits visitor hashes when visitor hashing is disabled', function (): void {
    config()->set('capell-site-search.hash_visitor_data', false);

    $log = RecordSiteSearchAction::run(
        new SearchRequestData(query: 'Laravel Search'),
        1,
        Request::create('/search', Symfony\Component\HttpFoundation\Request::METHOD_GET, server: [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_USER_AGENT' => 'Capell Test Browser',
        ]),
    );

    expect($log)->toBeInstanceOf(SiteSearchLog::class);
    expect($log->ip_hash)->toBeNull();
    expect($log->user_agent_hash)->toBeNull();
});

test('records clicked result url on an existing search log', function (): void {
    $log = SiteSearchLog::factory()->create([
        'clicked_result_url' => null,
    ]);

    $updatedLog = RecordSearchResultClickAction::run($log, '/search-result');

    expect($updatedLog->clicked_result_url)->toBe('/search-result');
    expect($log->refresh()->clicked_result_url)->toBe('/search-result');
});
