<?php

declare(strict_types=1);

use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;

beforeEach(function (): void {
    $blogCreator = resolve(BlogCreator::class);
    $blogCreator->createBlogPageType();
});

it('retrieves blog page for a site', function (): void {
    $site = Site::factory()->create();

    $blogPage = Page::factory()
        ->site($site)
        ->create();

    $blogPage->type()->associate(
        $site->workspace->types()
            ->where('key', BlogPageTypeEnum::Blog->value)
            ->first(),
    )->save();

    $foundPage = BlogLoader::getBlogPage($site);

    expect($foundPage)->toBeInstanceOf(Page::class);
});

it('returns null when blog page does not exist', function (): void {
    $site = Site::factory()->create();

    $foundPage = BlogLoader::getBlogPage($site);

    expect($foundPage)->toBeNull();
});

it('retrieves blog page url by language', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $language = $site->translations->first()->language;

    $blogPage = Page::factory()
        ->site($site)
        ->create();

    $blogPage->type()->associate(
        $site->workspace->types()
            ->where('key', BlogPageTypeEnum::Blog->value)
            ->first(),
    )->save();

    PageUrl::factory()
        ->page($blogPage)
        ->language($language)
        ->create();

    $url = BlogLoader::getBlogPageUrl($site, $language);

    expect($url)->toBeInstanceOf(PageUrl::class);
});
