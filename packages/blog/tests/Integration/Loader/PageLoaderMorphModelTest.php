<?php

declare(strict_types=1);

use Capell\Blog\Models\Article;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Tests\Support\Concerns\TestingFrontend;

uses(TestingFrontend::class);

beforeEach(function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();

    // Required so ArticleTranslationSavedListener can construct valid page URLs
    resolve(BlogCreator::class)->createBlogPage($site);

    Article::factory()
        ->count(3)
        ->for($site)
        ->withTranslations($language)
        ->create();

    $this->language = $language;
    $this->site = $site;
});

it('returns Article instances not Page instances when morphModel is Article class', function (): void {
    $results = PageLoader::getPages(
        language: $this->language,
        site: $this->site,
        morphModel: Article::class,
        useCache: false,
    );

    expect($results)->not->toBeEmpty()
        ->each(fn ($item) => $item->toBeInstanceOf(Article::class)->not->toBeInstanceOf(Page::class));
});

it('returns Article instances not Page instances when morphModel is article morph alias', function (): void {
    $results = PageLoader::getPages(
        language: $this->language,
        site: $this->site,
        morphModel: 'article',
        useCache: false,
    );

    expect($results)->not->toBeEmpty()
        ->each(fn ($item) => $item->toBeInstanceOf(Article::class)->not->toBeInstanceOf(Page::class));
});
