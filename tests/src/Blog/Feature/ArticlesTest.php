<?php

declare(strict_types=1);

use Capell\Blog\Actions\GenerateArchivePageUrl;
use Capell\Blog\Data\ArchiveMonthData;
use Capell\Blog\Enums\TagTypeEnum;
use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;
use Capell\Blog\Services\Creator\BlogCreator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

uses(TestingFrontend::class);

test('blog page lists articles', function (): void {
    $blogCreator = app(BlogCreator::class);

    $siteDomain = SiteDomain::factory()->default()->create();
    $site = $siteDomain->site;

    $blogPage = $blogCreator->createBlogPage($site);
    $blogPageUrl = $blogPage->pageUrl;

    $articleType = $blogCreator->createArticlePageType();
    $articleLayout = $blogCreator->createArticleLayout(createWidgets: true);

    $articles = Article::factory()
        ->count(3)
        ->site($siteDomain->site)
        ->layout($articleLayout)
        ->type($articleType)
        ->parent($blogPage)
        ->withTranslations($site->languages)
        ->create();

    expect($blogPage)
        ->toBeInstanceOf(Page::class)
        ->type->name->toBe('Blog')
        ->layout->name->toBe('Blog Posts');

    get($blogPageUrl->full_url)
        ->assertOk()
        ->assertSeeHtml($blogPage->title)
        ->assertSeeHtml($articles[0]->title)
        ->assertSeeHtml($articles[1]->title)
        ->assertSeeHtml($articles[2]->title);
});

test('visit blogs page with no articles and see appropriate message', function (): void {
    $blogCreator = app(BlogCreator::class);

    $siteDomain = SiteDomain::factory()->default()->create();
    $site = $siteDomain->site;

    $blogPage = $blogCreator->createBlogPage($site);
    $blogPageUrl = $blogPage->pageUrl;

    expect($blogPage)
        ->toBeInstanceOf(Page::class)
        ->type->name->toBe('Blog')
        ->layout->name->toBe('Blog Posts');

    get($blogPageUrl->full_url)
        ->assertOk()
        ->assertSeeText(__('blog::messages.no_articles_found'));
});

test('article page', function (): void {
    $blogCreator = app(BlogCreator::class);

    $siteDomain = SiteDomain::factory()->default()->create();
    $site = $siteDomain->site;

    $blogPage = $blogCreator->createBlogPage($site);
    $articleType = $blogCreator->createArticlePageType();
    $articleLayout = $blogCreator->createArticleLayout(createWidgets: true);

    $article = Article::factory()
        ->site($siteDomain->site)
        ->layout($articleLayout)
        ->type($articleType)
        ->parent($blogPage)
        ->withTranslations($site->languages)
        ->create();

    expect($article)
        ->toBeInstanceOf(Page::class)
        ->type->name->toBe('Article')
        ->layout->name->toBe('Article')
        ->parent->name->toBe('Blog');

    get($article->pageUrl->full_url)
        ->assertOk()
        ->assertSeeHtml(e($article->title))
        ->assertSeeHtml(e($blogPage->label));
});

test('article page list tags', function (): void {
    $blogCreator = app(BlogCreator::class);

    $langauge = Language::factory()->create();
    $site = Site::factory()->recycle($langauge)->withTranslations()->create();
    $tags = Tag::factory()->count(3)->translate($langauge)->type(TagTypeEnum::Page)->create();

    $blogPage = $blogCreator->createBlogPage($site);

    $tagsPage = $blogCreator->createTagsPage($site, $site->languages, createWidgets: true);
    $tagPage = $blogCreator->createTagPage($site, $tagsPage, $site->languages);

    $archivesPage = $blogCreator->createArchivesPage($blogPage);
    $archivePage = $blogCreator->createArchivePage($archivesPage);

    $articleType = $blogCreator->createArticlePageType();
    $articleLayout = $blogCreator->createArticleLayout(createWidgets: true);

    $article = Article::factory()
        ->site($site)
        ->layout($articleLayout)
        ->type($articleType)
        ->parent($blogPage)
        ->withTranslations()
        ->hasAttached($tags)
        ->create();

    $archiveUrl = GenerateArchivePageUrl::run(
        $archivePage->pageUrl,
        ArchiveMonthData::fromDate($article->publish_from ?? $article->created_at),
    );

    get($article->pageUrl->full_url)
        ->assertOk()
        ->assertSeeHtml(e($article->title))
        ->assertSeeHtml(e($blogPage->label))
        ->assertSee($tags[0]->translate('name', $langauge->code))
        ->assertSeeHtml('href="' . $tags[0]->getPageUrl($tagPage, $langauge) . '"')
        ->assertSeeHtml('href="' . $archiveUrl . '"');
});
