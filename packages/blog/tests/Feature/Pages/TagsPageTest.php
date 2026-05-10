<?php

declare(strict_types=1);

use Capell\Blog\Models\Article;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\Support\Sitemap\TagsSitemap;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Tags\Enums\TagTypeEnum;
use Capell\Tags\Models\Tag;
use Capell\Tests\Support\Concerns\TestingFrontend;

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
    $blogPage = $blogCreator->createBlogPage($site);
    $tagsPage = $blogCreator->createTagsPage($site, $blogPage, createWidgets: true);
    $tagPage = $blogCreator->createTagPage($site, $tagsPage);

    expect($tagsPage)
        ->toBeInstanceOf(Page::class)
        ->name->toBe('Tags Page')
        ->type->name->toBe('capell::generic.system')
        ->layout->name->toBe('Tags')
        ->translation->language->id->toBe($language->id)
        ->pageUrl->language->id->toBe($language->id)
        ->and($tagPage)
        ->toBeInstanceOf(Page::class)
        ->name->toBe('Tag Page')
        ->type->name->toBe('Tag Page')
        ->layout->name->toBe('Results')
        ->translation->language->id->toBe($language->id)
        ->pageUrl->language->id->toBe($language->id);

    get($tagsPage->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            'title',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($tagsPage->translation->title . ' | ' . $site->title),
        )
        ->assertElementExists(
            'main',
            fn (AssertElement $main): BaseAssert => $main->doesntContain('.no-results')
                ->containsText($tags[0]->translate('name', $language->code)),
        )
        ->assertSeeHtml('href="' . $tags[0]->getUrl($tagPage, $language) . '"')
        ->assertSee($tags[1]->translate('name', $language->code))
        ->assertSeeHtml('href="' . $tags[1]->getUrl($tagPage, $language) . '"')
        ->assertDontSeeText($tags[2]->translate('name', $language->code));
});

test('tags sitemap formats tag urls from the generated tag results page', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $language = Language::factory()->create();
    $siteDomain = SiteDomain::factory()->default()->recycle($language)->create();
    $site = $siteDomain->site;
    $tag = Tag::factory()->translate($language)->type(TagTypeEnum::Page)->create();
    $tag->setAttribute('taggables_count', 4);

    $blogPage = $blogCreator->createBlogPage($site);
    $tagsPage = $blogCreator->createTagsPage($site, $blogPage);
    $tagPage = $blogCreator->createTagPage($site, $tagsPage);

    $sitemap = new TagsSitemap($site, $siteDomain, $language);
    $sitemapPage = $sitemap->format($tagPage, $tag);

    expect($sitemapPage->label)->toBe($tag->getTranslation('name', $language->code) . ' (4)')
        ->and($sitemapPage->url)->toBe($tag->getUrl($tagPage, $language))
        ->and($sitemapPage->editUrl)->toBeNull()
        ->and($sitemapPage->pageableType)->toBe($tag->getMorphClass())
        ->and($sitemapPage->pageId)->toBe($tag->id);
});
