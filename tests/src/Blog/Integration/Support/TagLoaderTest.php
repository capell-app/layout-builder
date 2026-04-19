<?php

declare(strict_types=1);

use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\Support\Loader\TagLoader;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;

beforeEach(function (): void {
    $blogCreator = resolve(BlogCreator::class);
    $blogCreator->createBlogPageType();
    $blogCreator->createTagPageType();
});

it('retrieves tags for a page', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $language = $site->translations->first()->language;

    $article = Article::factory()->site($site)->create();

    $tag = Tag::factory()->site($site)->create();
    $article->tags()->attach($tag);

    $page = Page::factory()
        ->site($site)
        ->create();

    $page->type()->associate(
        $site->workspace->types()
            ->where('key', BlogPageTypeEnum::Article->value)
            ->first(),
    )->save();

    $tags = TagLoader::getPageTags($page);

    expect($tags)->toHaveCount(1)
        ->first()->toBeInstanceOf(Tag::class);
});

it('returns empty collection when page has no tags', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $page = Page::factory()
        ->site($site)
        ->create();

    $tags = TagLoader::getPageTags($page);

    expect($tags)->toHaveCount(0);
});

it('retrieves tag results page for a site', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $language = $site->translations->first()->language;

    $tagPage = Page::factory()
        ->site($site)
        ->create();

    $tagPage->type()->associate(
        $site->workspace->types()
            ->where('key', BlogPageTypeEnum::Tag->value)
            ->first(),
    )->save();

    $foundPage = TagLoader::getTagResultsPage($site, $language);

    expect($foundPage)->toBeInstanceOf(Page::class);
});

it('returns null when tag results page does not exist', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $language = $site->translations->first()->language;

    $foundPage = TagLoader::getTagResultsPage($site, $language);

    expect($foundPage)->toBeNull();
});
