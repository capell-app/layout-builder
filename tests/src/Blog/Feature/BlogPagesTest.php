<?php

declare(strict_types=1);

use Capell\Blog\Models\Article;
use Capell\Blog\Services\BlogCreator;
use Capell\Core\Models\Page;
use Capell\Core\Models\SiteDomain;
use Capell\Frontend\Actions\ReplacePageDataAction;
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

    $articles = Page::factory()
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

test('article page', function (): void {
    $blogCreator = app(BlogCreator::class);

    $siteDomain = SiteDomain::factory()->default()->create();
    $site = $siteDomain->site;

    $blogPage = $blogCreator->createBlogPage($site);
    $articleType = $blogCreator->createArticlePageType();
    $articleLayout = $blogCreator->createArticleLayout(createWidgets: true);

    $articles = Page::factory()
        ->count(3)
        ->site($siteDomain->site)
        ->layout($articleLayout)
        ->type($articleType)
        ->parent($blogPage)
        ->withTranslations($site->languages)
        ->create();

    $article = $articles->get(1);

    $articleUrl = $article->pageUrl;

    expect($article)
        ->toBeInstanceOf(Page::class)
        ->type->name->toBe('Article')
        ->layout->name->toBe('Article')
        ->parent->name->toBe('Blog');

    get($articleUrl->full_url)
        ->assertOk()
        ->assertSeeHtml(e($article->title))
        ->assertSeeHtml(e($blogPage->label));
});

test('archives page list articles archives by month/year', function (): void {
    $blogCreator = app(BlogCreator::class);

    $siteDomain = SiteDomain::factory()->default()->create();
    $site = $siteDomain->site;

    $blogPage = $blogCreator->createBlogPage($site);
    $archivesPage = $blogCreator->createArchivesPage($blogPage);
    $archivePage = $blogCreator->createArchivePage($archivesPage);
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

    $oldestArticle = $articles->sortBy(fn (Page $page) => $page->publish_from ?? $page->created_at)->first();
    $oldestPublishDate = $oldestArticle->publish_from ?: $oldestArticle->created_at;
    $oldestArchiveUrl = $archivePage->pageUrl->full_url . '/' . $oldestPublishDate->year . '-' . $oldestPublishDate->month;

    $newestArticle = $articles->sortByDesc(fn (Page $page) => $page->publish_from ?? $page->created_at)->first();
    $newestPublishDate = $newestArticle->publish_from ?: $newestArticle->created_at;
    $newestArchiveUrl = $archivePage->pageUrl->full_url . '/' . $newestPublishDate->year . '-' . $newestPublishDate->month;

    expect($archivesPage)
        ->toBeInstanceOf(Page::class)
        ->type->name->toBe('System')
        ->layout->name->toBe('Archives')
        ->parent->name->toBe('Blog');

    get($archivesPage->pageUrl->full_url)
        ->assertOk()
        ->assertSee($archivesPage->title)
        ->assertSeeHtml('href="' . $oldestArchiveUrl . '"')
        ->assertSeeHtml('href="' . $newestArchiveUrl . '"');
});

test('archive page list articles by month/year', function (): void {
    $blogCreator = app(BlogCreator::class);

    $siteDomain = SiteDomain::factory()->default()->create();
    $site = $siteDomain->site;

    $blogPage = $blogCreator->createBlogPage($site);
    $archivesPage = $blogCreator->createArchivesPage($blogPage);
    $archivePage = $blogCreator->createArchivePage($archivesPage);
    $articleType = $blogCreator->createArticlePageType();
    $articleLayout = $blogCreator->createArticleLayout(createWidgets: true);

    $publishDate = now()->subMonth();

    $articles = Article::factory()
        ->count(3)
        ->site($siteDomain->site)
        ->layout($articleLayout)
        ->type($articleType)
        ->parent($blogPage)
        ->withTranslations($site->languages)
        ->state([
            'publish_from' => fake()->dateTimeBetween($publishDate->startOfMonth(), $publishDate->endOfMonth()),
        ])
        ->create();

    $archivePageUrl = $archivePage->pageUrl;

    $archiveUrl = $archivePageUrl->full_url . '/' . $publishDate->year . '-' . $publishDate->month;

    expect($archivePage)
        ->toBeInstanceOf(Page::class)
        ->type->name->toBe('Archive')
        ->layout->name->toBe('Results')
        ->parent->name->toBe('Archives')
        ->and($archivePage->getAncestors(['name'])->pluck('name')->toArray())
        ->toEqual(['Blog', 'Archives']);

    get($archiveUrl)
        ->assertOk()
        ->assertSeeHtml('<title>' . ReplacePageDataAction::run($archivePage->title, ['archive_month' => $publishDate->format('F'), 'archive_year' => $publishDate->year]))
        ->assertDontSeeText('No results found');
});

todo('visit tag page and list articles by tag');

todo('visit blogs page with no articles and see appropriate message');
