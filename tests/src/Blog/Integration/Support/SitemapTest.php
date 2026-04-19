<?php

declare(strict_types=1);

use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\Support\Sitemap\ArchivesSitemap;
use Capell\Blog\Support\Sitemap\ArticlesSitemap;
use Capell\Blog\Support\Sitemap\TagsSitemap;
use Capell\Core\Models\Site;

beforeEach(function (): void {
    $blogCreator = resolve(BlogCreator::class);
    $blogCreator->createBlogPageType();
    $blogCreator->createArticlePageType();
    $blogCreator->createTagPageType();
    $blogCreator->createArchivePageType();
});

it('generates articles sitemap with published articles', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $language = $site->translations->first()->language;

    Article::factory()
        ->site($site)
        ->language($language)
        ->create(['visible_from' => now()->subDays(1)]);

    Article::factory()
        ->site($site)
        ->language($language)
        ->create(['visible_from' => now()->addDays(1)]);

    $sitemap = resolve(ArticlesSitemap::class);
    $urls = $sitemap->getUrls($site, $language);

    expect($urls)->toHaveLength(1);
});

it('generates tags sitemap with tags', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $language = $site->translations->first()->language;

    Tag::factory()->site($site)->create();
    Tag::factory()->site($site)->create();

    $sitemap = resolve(TagsSitemap::class);
    $urls = $sitemap->getUrls($site, $language);

    expect($urls)->toHaveLength(2);
});

it('generates archives sitemap with monthly archives', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $language = $site->translations->first()->language;

    Article::factory()
        ->site($site)
        ->language($language)
        ->create(['visible_from' => '2025-01-15 10:00:00']);

    Article::factory()
        ->site($site)
        ->language($language)
        ->create(['visible_from' => '2025-02-20 10:00:00']);

    $sitemap = resolve(ArchivesSitemap::class);
    $urls = $sitemap->getUrls($site, $language);

    expect($urls)->toHaveLength(2);
});
