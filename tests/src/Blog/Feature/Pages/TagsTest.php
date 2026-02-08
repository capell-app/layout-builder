<?php

declare(strict_types=1);

use Capell\Blog\Enums\TagTypeEnum;
use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

test('tags page list tags', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    $tags = Tag::factory()->count(3)->translate($language)->type(TagTypeEnum::Page)->create();
    $articleType = $blogCreator->createArticlePageType();
    Article::factory()
        ->count(5)
        ->recycle($site)
        ->type($articleType)
        ->withTranslations()
        ->hasAttached($tags->slice(0, 2))
        ->create();
    $blogPage = $blogCreator->createBlogPage($site, languages: $site->languages);
    $tagsPage = $blogCreator->createTagsPage($site, $blogPage, $site->languages, createWidgets: true);
    $tagPage = $blogCreator->createTagPage($site, $tagsPage, $site->languages);

    expect($tagsPage)
        ->toBeInstanceOf(Page::class)
        ->name->toBe('Tags Page (show all tags)')
        ->type->name->toBe('System')
        ->layout->name->toBe('Tags')
        ->translation->language->id->toBe($language->id)
        ->pageUrl->language->id->toBe($language->id)
        ->and($tagPage)
        ->toBeInstanceOf(Page::class)
        ->name->toBe('Tag Page (show all articles with this tag)')
        ->type->name->toBe('Tag Page (show all articles with this tag)')
        ->layout->name->toBe('Results')
        ->translation->language->id->toBe($language->id)
        ->pageUrl->language->id->toBe($language->id);

    get($tagsPage->pageUrl->full_url)
        ->assertOk()
        ->assertSeeText($tagsPage->translation->title)
        ->assertElementExists(
            'main',
            fn (AssertElement $main): BaseAssert => $main->containsText($tags[0]->translate('name', $language->code)),
        )
        ->assertSeeHtml('href="' . $tags[0]->getPageUrl($tagPage, $language) . '"')
        ->assertSee($tags[1]->translate('name', $language->code))
        ->assertSeeHtml('href="' . $tags[1]->getPageUrl($tagPage, $language) . '"')
        ->assertDontSeeText($tags[2]->translate('name', $language->code));
});

test('tag page list articles by tag', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    $tag = Tag::factory()->translate($language)->type(TagTypeEnum::Page)->create();
    $articles = Article::factory()->count(5)->recycle($site)->withTranslations()->hasAttached($tag)->create();
    $blogPage = $blogCreator->createBlogPage($site, languages: $site->languages);
    $tagsPage = $blogCreator->createTagsPage($site, $blogPage, $site->languages, createWidgets: true);
    $tagPage = $blogCreator->createTagPage($site, $tagsPage, $site->languages);

    $title = trans($tagPage->translation->title, ['tag_name' => $tag->translate('name', $language->code)]);

    expect($tagPage)
        ->translation->title->toBe(':Tag_name Articles')
        ->layout->getContainerWidgets()->toContain('breadcrumbs');

    get($tag->getPageUrl($tagPage, $language))
        ->assertOk()
        ->assertDontSeeText(':Tag_name Articles')
        ->assertElementExists(
            'title',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($title . ' | ' . $site->title),
        )
        ->assertElementExists(
            'h1',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($title),
        )
        ->assertSeeInOrder($articles->map(fn (Page $page) => $page->translation->title)->all());
});
