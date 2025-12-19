<?php

declare(strict_types=1);

use Capell\Blog\Actions\GenerateArchivePageUrl;
use Capell\Blog\Data\ArchiveMonthData;
use Capell\Blog\Models\Article;
use Capell\Blog\Services\BlogCreator;
use Capell\Core\Models\Page;
use Capell\Core\Models\SiteDomain;
use Capell\Frontend\Actions\ReplacePageDataAction;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

uses(TestingFrontend::class);

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
    $oldestArchiveUrl = GenerateArchivePageUrl::run($archivePage->pageUrl, ArchiveMonthData::fromDate($oldestPublishDate));

    $newestArticle = $articles->sortByDesc(fn (Page $page) => $page->publish_from ?? $page->created_at)->first();
    $newestPublishDate = $newestArticle->publish_from ?: $newestArticle->created_at;
    $newestArchiveUrl = GenerateArchivePageUrl::run($archivePage->pageUrl, ArchiveMonthData::fromDate($newestPublishDate));

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

    $archiveUrl = GenerateArchivePageUrl::run($archivePage->pageUrl, ArchiveMonthData::fromDate($publishDate));

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
        ->assertDontSeeText('no-results');
});

test('error page when no articles found for given month/year', function (string $slug): void {
    $blogCreator = app(BlogCreator::class);

    $siteDomain = SiteDomain::factory()->default()->create();
    $site = $siteDomain->site;

    $blogPage = $blogCreator->createBlogPage($site);
    $archivesPage = $blogCreator->createArchivesPage($blogPage);
    $archivePage = $blogCreator->createArchivePage($archivesPage);

    $archivePageUrl = $archivePage->pageUrl;

    $archiveUrl = $archivePageUrl->full_url . $slug;

    get($archiveUrl)
        ->assertNotFound();
})->with(['/2000-01', '/text-06']);
