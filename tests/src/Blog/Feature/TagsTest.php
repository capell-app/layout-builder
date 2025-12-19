<?php

declare(strict_types=1);

use Capell\Blog\Enums\TagTypeEnum;
use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;
use Capell\Blog\Services\BlogCreator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;

uses(TestingFrontend::class);

test('tags page list tags', function (): void {
    $blogCreator = app(BlogCreator::class);

    $langauge = Language::factory()->create();
    $site = Site::factory()->recycle($langauge)->withTranslations()->create();
    $tags = Tag::factory()->count(3)->translate($langauge)->type(TagTypeEnum::Page)->create();
    Article::factory()->recycle($site)->withTranslations()->count(5)->hasAttached($tags->slice(0, 2))->create();

    $tagsPage = $blogCreator->createTagsPage($site, $site->languages, createWidgets: true);
    $tagPage = $blogCreator->createTagPage($site, $tagsPage, $site->languages);

    expect($tagsPage)
        ->toBeInstanceOf(Page::class)
        ->name->toBe('Tags Page')
        ->type->name->toBe('System')
        ->layout->name->toBe('Tags')
        ->translation->language->id->toBe($langauge->id)
        ->pageUrl->language->id->toBe($langauge->id)
        ->and($tagPage)
        ->toBeInstanceOf(Page::class)
        ->name->toBe('Tag Results')
        ->type->name->toBe('Tag Results')
        ->layout->name->toBe('Results')
        ->translation->language->id->toBe($langauge->id)
        ->pageUrl->language->id->toBe($langauge->id);

    get($tagsPage->pageUrl->full_url)
        ->assertOk()
        ->assertSeeText($tagsPage->translation->title)
        ->assertElementExists(
            'main',
            fn (AssertElement $main) => $main->containsText($tags[0]->translate('name', $langauge->code)),
        )
        ->assertSeeHtml('href="' . $tags[0]->getPageUrl($tagPage, $langauge) . '"')
        ->assertSee($tags[1]->translate('name', $langauge->code))
        ->assertSeeHtml('href="' . $tags[1]->getPageUrl($tagPage, $langauge) . '"')
        ->assertDontSeeText($tags[2]->translate('name', $langauge->code));
});

test('tag page list articles by tag', function (): void {
    $blogCreator = app(BlogCreator::class);

    $langauge = Language::factory()->create();
    $site = Site::factory()->recycle($langauge)->withTranslations()->create();
    $tag = Tag::factory()->translate($langauge)->type(TagTypeEnum::Page)->create();
    $articles = Article::factory()->count(5)->recycle($site)->withTranslations()->hasAttached($tag)->create();

    $tagsPage = $blogCreator->createTagsPage($site, $site->languages, createWidgets: true);
    $tagPage = $blogCreator->createTagPage($site, $tagsPage, $site->languages);

    get($tag->getPageUrl($tagPage, $langauge))
        ->assertOk()
        ->assertSeeText($tagPage->translation->title)
        ->assertSeeText($tag->translate('name', $langauge->code))
        ->assertSeeInOrder($articles->map(fn (Page $page) => $page->translation->title)->toArray());
});
