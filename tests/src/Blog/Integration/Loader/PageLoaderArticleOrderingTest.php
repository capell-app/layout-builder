<?php

declare(strict_types=1);

use Capell\Blog\Models\Article;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

uses(TestingFrontend::class);

beforeEach(function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();

    // Ensures ArticleTranslationSavedListener can construct valid page URLs
    resolve(BlogCreator::class)->createBlogPage($site);

    // Bravo: middle published date (2026-02-01), middle alphabetically
    $articleBravo = Article::factory()
        ->for($site)
        ->published(CarbonImmutable::parse('2026-02-01 00:00:00'))
        ->withTranslations($language, ['title' => 'Bravo'])
        ->create();

    // Alpha: newest published date (2026-03-01), first alphabetically
    $articleAlpha = Article::factory()
        ->for($site)
        ->published(CarbonImmutable::parse('2026-03-01 00:00:00'))
        ->withTranslations($language, ['title' => 'Alpha'])
        ->create();

    // Charlie: oldest published date (2026-01-01), last alphabetically
    $articleCharlie = Article::factory()
        ->for($site)
        ->published(CarbonImmutable::parse('2026-01-01 00:00:00'))
        ->withTranslations($language, ['title' => 'Charlie'])
        ->create();

    $this->language = $language;
    $this->site = $site;
    $this->articleIds = [$articleBravo->id, $articleAlpha->id, $articleCharlie->id];
    // Latest: newest published_at first → Alpha, Bravo, Charlie
    $this->expectedLatestOrder = [$articleAlpha->id, $articleBravo->id, $articleCharlie->id];
    // Oldest: oldest published_at first → Charlie, Bravo, Alpha
    $this->expectedOldestOrder = [$articleCharlie->id, $articleBravo->id, $articleAlpha->id];
    // Alphabetical: Alpha, Bravo, Charlie
    $this->expectedAlphabeticalOrder = [$articleAlpha->id, $articleBravo->id, $articleCharlie->id];
});

function loadArticlesForOrderingTest(
    Language $language,
    Site $site,
    array $articleIds,
    ?PageOrderEnum $ordering,
): Collection {
    /** @var Collection<int, Article> */
    return PageLoader::getPages(
        language: $language,
        site: $site,
        ordering: $ordering,
        morphModel: Article::class,
        useCache: false,
        modifyQuery: fn ($query) => $query->whereIn('id', $articleIds),
    );
}

it('orders articles by newest published date when ordering is latest', function (): void {
    $articles = loadArticlesForOrderingTest($this->language, $this->site, $this->articleIds, PageOrderEnum::Latest);

    expect($articles->pluck('id')->all())->toBe($this->expectedLatestOrder);
});

it('orders articles by oldest published date when ordering is oldest', function (): void {
    $articles = loadArticlesForOrderingTest($this->language, $this->site, $this->articleIds, PageOrderEnum::Oldest);

    expect($articles->pluck('id')->all())->toBe($this->expectedOldestOrder);
});

it('orders articles alphabetically by title when ordering is alphabetical', function (): void {
    $articles = loadArticlesForOrderingTest($this->language, $this->site, $this->articleIds, PageOrderEnum::Alphabetical);

    expect($articles->pluck('id')->all())->toBe($this->expectedAlphabeticalOrder);
});

it('defaults to latest ordering when no ordering is specified', function (): void {
    $articles = loadArticlesForOrderingTest($this->language, $this->site, $this->articleIds, null);

    expect($articles->pluck('id')->all())->toBe($this->expectedLatestOrder);
});
