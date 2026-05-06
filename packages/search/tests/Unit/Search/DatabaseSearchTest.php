<?php

declare(strict_types=1);

use Capell\Search\Data\SearchResultData;
use Capell\Search\Drivers\DatabaseSearch;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Blueprint;

// Bootstrap an in-memory SQLite database once for all tests in this file.
beforeAll(function (): void {
    $capsule = new Capsule;
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    Capsule::schema()->create('pages', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('title');
        $table->text('excerpt')->nullable();
        $table->text('body')->nullable();
        $table->string('slug');
        $table->string('type')->default('page');
    });

    Capsule::table('pages')->insert([
        ['title' => 'Laravel Tutorial', 'excerpt' => 'Learn Laravel today', 'body' => null, 'slug' => 'laravel-tutorial', 'type' => 'post'],
        ['title' => 'About Us', 'excerpt' => 'Company info', 'body' => 'We are a company', 'slug' => 'about', 'type' => 'page'],
        ['title' => 'Contact', 'excerpt' => null, 'body' => 'Reach out anytime', 'slug' => 'contact', 'type' => 'page'],
        ['title' => '50% Discount', 'excerpt' => 'Literal percent offer', 'body' => null, 'slug' => 'percent', 'type' => 'page'],
        ['title' => '500 Discount', 'excerpt' => 'Numeric offer', 'body' => null, 'slug' => 'numeric', 'type' => 'page'],
    ]);
});

test('returns empty paginator for empty query', function (): void {
    $databaseConnection = Mockery::mock(ConnectionInterface::class);

    $search = new DatabaseSearch($databaseConnection);
    $results = $search->search('   ');

    expect($results->total())->toBe(0);
    expect($results->isEmpty())->toBeTrue();
});

test('search returns matching results from the database', function (): void {
    $search = new DatabaseSearch(Capsule::connection());

    $results = $search->search('Laravel');

    expect($results->total())->toBe(1);
    expect($results->first())->toBeInstanceOf(SearchResultData::class);
    expect($results->first()->title)->toBe('Laravel Tutorial');
});

test('search result url is prefixed with a leading slash', function (): void {
    $search = new DatabaseSearch(Capsule::connection());

    $results = $search->search('Laravel');

    expect($results->first()->url)->toBe('/laravel-tutorial');
});

test('search falls back to body when excerpt is null', function (): void {
    $search = new DatabaseSearch(Capsule::connection());

    $results = $search->search('anytime');

    expect($results->total())->toBe(1);
    expect($results->first()->excerpt)->toContain('anytime');
});

test('search returns all matches when multiple rows satisfy query', function (): void {
    $search = new DatabaseSearch(Capsule::connection());

    $results = $search->search('ar');

    expect($results->total())->toBeGreaterThanOrEqual(2);
});

test('search paginates correctly', function (): void {
    $search = new DatabaseSearch(Capsule::connection());

    $firstPage = $search->search('ar', perPage: 1, page: 1);
    $secondPage = $search->search('ar', perPage: 1, page: 2);

    expect($firstPage->count())->toBe(1);
    expect($secondPage->count())->toBe(1);
    expect($firstPage->first()->url)->not->toBe($secondPage->first()->url);
});

test('search requires a meaningful minimum query length', function (): void {
    $search = new DatabaseSearch(Capsule::connection());

    $results = $search->search('a');

    expect($results->total())->toBe(0);
    expect($results->isEmpty())->toBeTrue();
});

test('search escapes like wildcards', function (): void {
    $search = new DatabaseSearch(Capsule::connection());

    $results = $search->search('50%');

    expect($results->total())->toBe(1);
    expect($results->first()->title)->toBe('50% Discount');
});

test('search clamps pagination arguments', function (): void {
    $search = new DatabaseSearch(Capsule::connection());

    $results = $search->search('Laravel', perPage: 500, page: -10);

    expect($results->perPage())->toBe(100);
    expect($results->currentPage())->toBe(1);
});

test('search score is a float', function (): void {
    $search = new DatabaseSearch(Capsule::connection());

    $results = $search->search('Laravel');

    expect($results->first()->score)->toBeFloat();
});

test('wraps matches in <mark> tags with escaping', function (): void {
    $databaseConnection = Mockery::mock(ConnectionInterface::class);
    $search = new DatabaseSearch($databaseConnection);

    $html = $search->highlight('<b>Laravel is great</b> for sites', 'Laravel');

    expect($html)
        ->toContain('<mark>Laravel</mark>')
        ->toContain('&lt;b&gt;');
});

test('highlight returns escaped text when query is empty', function (): void {
    $databaseConnection = Mockery::mock(ConnectionInterface::class);
    $search = new DatabaseSearch($databaseConnection);

    $html = $search->highlight('<script>alert(1)</script>', '');

    expect($html)->toContain('&lt;script&gt;');
    expect($html)->not->toContain('<mark>');
});
