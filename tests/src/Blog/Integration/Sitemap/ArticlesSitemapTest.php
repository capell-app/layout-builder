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
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;
use Illuminate\Support\Collection;

uses(TestingFrontend::class);

it('returns blog page with all Article children recursively', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    $domain = SiteDomain::factory()->for($site)->create();

    // Create a blog page and a tree of children
    $blogPage = $blogCreator->createBlogPage($site, languages: $site->languages);

    // Children: Article group
    $articleA = Page::factory()->for($site)->withTranslations()->state(['type_group' => BlogTypeGroupEnum::Article->value])->create();
    $articleB = Page::factory()->for($site)->withTranslations()->state(['type_group' => BlogTypeGroupEnum::Article->value])->create();
    $nonArticle = Page::factory()->for($site)->withTranslations()->state(['type_group' => 'other'])->create();

    $articleA->parent()->associate($blogPage)->save();
    $articleB->parent()->associate($blogPage)->save();
    $nonArticle->parent()->associate($blogPage)->save();

    // Nested article under A
    $nestedArticle = Page::factory()->for($site)->withTranslations()->state(['type_group' => BlogTypeGroupEnum::Article->value])->create();
    $nestedArticle->parent()->associate($articleA)->save();

    $sitemap = new ArticlesSitemap(site: $site, domain: $domain, language: $language);
    $result = $sitemap->fetch();

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(1);

    /** @var SitemapPageData $root */
    $root = $result->first();
    expect($root)->toBeInstanceOf(SitemapPageData::class);
    expect($root->page_id)->toBe($blogPage->id);

    // Children should include articleA and articleB, but exclude non-article
    $childrenLabels = $root->children->map(fn (SitemapPageData $d) => $d->page_id)->all();

    expect($childrenLabels)->toContain($articleA->id);
    expect($childrenLabels)->toContain($articleB->id);
    expect($childrenLabels)->not->toContain($nonArticle->id);

    // Recursion: articleA should have nestedArticle as child
    /** @var SitemapPageData $articleANode */
    $articleANode = $root->children->firstWhere(fn (SitemapPageData $d) => $d->page_id === $articleA->id);
    expect($articleANode)->not()->toBeNull();
    expect($articleANode->children->map(fn (SitemapPageData $d) => $d->page_id)->all())->toContain($nestedArticle->id);
});
