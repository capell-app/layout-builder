<?php

declare(strict_types=1);

use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\Support\Sitemap\ArticlesSitemap;
use Capell\Core\Data\SitemapPageData;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Type;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Illuminate\Support\Collection;

uses(TestingFrontend::class);

it('returns blog page with all Article children recursively', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    $domain = SiteDomain::factory()->for($site)->create();

    // Create a blog page and a tree of children
    $blogPage = $blogCreator->createBlogPage($site);

    // Children: Article group
    $articleA = Page::factory()
        ->for($site)->parent($blogPage)
        ->withTranslations()
        ->for(Type::factory()->page()->group(BlogTypeGroupEnum::Article->value))
        ->create();
    $articleB = Page::factory()
        ->for($site)->parent($blogPage)
        ->withTranslations()
        ->for(Type::factory()->page()->group(BlogTypeGroupEnum::Article->value))
        ->create();
    $nonArticle = Page::factory()->for($site)->parent($blogPage)->withTranslations()->create();

    // Nested article under A
    $nestedArticle = Page::factory()
        ->for($site)
        ->withTranslations()
        ->parent($articleA)
        ->for(Type::factory()->page()->group(BlogTypeGroupEnum::Article->value))
        ->create();

    $sitemap = new ArticlesSitemap(site: $site, domain: $domain, language: $language);
    $result = $sitemap->fetch();

    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1);

    /** @var SitemapPageData $root */
    $root = $result->first();

    expect($root)
        ->toBeInstanceOf(SitemapPageData::class)
        ->pageId->toBe($blogPage->id)
        ->and($root->children)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(2)
        ->and($root->children->pluck('pageId'))
        ->toMatchArray([$articleA->id, $articleB->id])
        ->and($root->children->first()->children)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->first()->pageId->toBe($nestedArticle->id);
});
