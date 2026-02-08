<?php

declare(strict_types=1);

use Capell\Blog\Models\Tag;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\Support\Sitemap\TagsSitemap;
use Capell\Core\Data\SitemapPageData;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;
use Illuminate\Support\Collection;

uses(TestingFrontend::class);

it('builds recursive sitemap for tag results page with parent chain and tag children', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    $domain = SiteDomain::factory()->for($site)->create();
    $blogPage = $blogCreator->createBlogPage($site, languages: $site->languages);
    $tagsPage = $blogCreator->createTagsPage($site, $blogPage, $site->languages, createWidgets: false);
    $tagPage = $blogCreator->createTagPage($site, $tagsPage, $site->languages);

    // Create a parent chain: Home -> Section -> TagsPage -> TagPage
    $home = Page::factory()->for($site)->withTranslations()->create();
    $section = Page::factory()->for($site)->withTranslations()->create();

    $tagsPage->parent()->associate($section)->save();
    $section->parent()->associate($home)->save();

    // Create some tags
    /** @var Collection<int, Tag> $tags */
    $tags = Tag::factory()->count(3)->translate($language)->create();

    // Under test
    $sitemap = new TagsSitemap(site: $site, domain: $domain, language: $language);
    $result = $sitemap->fetch();

    // Assertions: top-level collection with single root
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(1);

    /** @var SitemapPageData $root */
    $root = $result->first();
    expect($root)->toBeInstanceOf(SitemapPageData::class);

    // Root should be Home, then Section, then TagsPage (children populated), then TagPage not present as separate level (TagPage is content page for tag links)
    expect($root->label)->toBe($home->translation->label);
    expect($root->children)->toBeInstanceOf(Collection::class);
    /** @var SitemapPageData $sectionNode */
    $sectionNode = $root->children->first();
    expect($sectionNode->label)->toBe($section->translation->label);
    expect($sectionNode->children)->toBeInstanceOf(Collection::class);
    /** @var SitemapPageData $tagsNode */
    $tagsNode = $sectionNode->children->first();
    expect($tagsNode->label)->toBe($tagsPage->translation->title);

    // Tag children
    expect($tagsNode->children)->toBeInstanceOf(Collection::class);
    expect($tagsNode->children->count())->toBeGreaterThan(0);

    // Each tag child should have a URL under tagPage pattern
    $firstTagChild = $tagsNode->children->first();
    expect($firstTagChild)->toBeInstanceOf(SitemapPageData::class);
    expect($firstTagChild->url)->toContain($tagPage->pageUrl->url !== null ? rtrim($tagPage->pageUrl->url, '/*') : $tagPage->pageUrl->full_url);
});
