<?php

declare(strict_types=1);

use Capell\Themes\Core\Search\DatabaseSiteSearch;
use Capell\Themes\Core\Search\SearchResult;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionInterface;

// Bootstrap an in-memory SQLite database once for all tests in this file.
beforeAll(function (): void {
    $capsule = new Capsule;
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    Capsule::schema()->create('pages', function ($table): void {
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
    ]);
});

test('returns empty paginator for empty query', function (): void {
    $db = Mockery::mock(ConnectionInterface::class);

    $search = new DatabaseSiteSearch($db);
    $results = $search->search('   ');

    expect($results->total())->toBe(0);
    expect($results->isEmpty())->toBeTrue();
});

test('search returns matching results from the database', function (): void {
    $search = new DatabaseSiteSearch(Capsule::connection());

    $results = $search->search('Laravel');

    expect($results->total())->toBe(1);
    expect($results->first())->toBeInstanceOf(SearchResult::class);
    expect($results->first()->title)->toBe('Laravel Tutorial');
});

test('search result url is prefixed with a leading slash', function (): void {
    $search = new DatabaseSiteSearch(Capsule::connection());

    $results = $search->search('Laravel');

    expect($results->first()->url)->toBe('/laravel-tutorial');
});

test('search falls back to body when excerpt is null', function (): void {
    $search = new DatabaseSiteSearch(Capsule::connection());

    $results = $search->search('anytime');

    expect($results->total())->toBe(1);
    expect($results->first()->excerpt)->toContain('anytime');
});

test('search returns all matches when multiple rows satisfy query', function (): void {
    $search = new DatabaseSiteSearch(Capsule::connection());

    $results = $search->search('a'); // matches all rows

    expect($results->total())->toBeGreaterThanOrEqual(2);
});

test('search paginates correctly', function (): void {
    $search = new DatabaseSiteSearch(Capsule::connection());

    $page1 = $search->search('a', perPage: 1, page: 1);
    $page2 = $search->search('a', perPage: 1, page: 2);

    expect($page1->count())->toBe(1);
    expect($page2->count())->toBe(1);
    expect($page1->first()->url)->not->toBe($page2->first()->url);
});

test('search score is a float', function (): void {
    $search = new DatabaseSiteSearch(Capsule::connection());

    $results = $search->search('Laravel');

    expect($results->first()->score)->toBeFloat();
});

test('wraps matches in <mark> tags with escaping', function (): void {
    $db = Mockery::mock(ConnectionInterface::class);
    $search = new DatabaseSiteSearch($db);

    $html = $search->highlight('<b>Laravel is great</b> for sites', 'Laravel');

    expect($html)
        ->toContain('<mark>Laravel</mark>')
        ->toContain('&lt;b&gt;');
});

test('highlight returns escaped text when query is empty', function (): void {
    $db = Mockery::mock(ConnectionInterface::class);
    $search = new DatabaseSiteSearch($db);

    $html = $search->highlight('<script>alert(1)</script>', '');

    expect($html)->toContain('&lt;script&gt;');
    expect($html)->not->toContain('<mark>');
});
