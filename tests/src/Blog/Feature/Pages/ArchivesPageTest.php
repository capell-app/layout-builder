<?php

declare(strict_types=1);

use Capell\Blog\Actions\GenerateArchiveUrl;
use Capell\Blog\Data\ArchiveMonthData;
use Capell\Blog\Models\Article;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Models\Page;
use Capell\Core\Models\SiteDomain;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Carbon\CarbonImmutable;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

test('archives page list articles archives by month/year', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $siteDomain = SiteDomain::factory()->default()->create();
    $site = $siteDomain->site;

    $blogPage = $blogCreator->createBlogPage($site);
    $archivesPage = $blogCreator->createArchivesPage($blogPage);
    $archivePage = $blogCreator->createArchivePage($archivesPage);
    $articleType = $blogCreator->createArticlePageType();
    $articleLayout = $blogCreator->createArticleLayout();
    $tagsPage = $blogCreator->createTagsPage($site, $blogPage);
    $blogCreator->createTagPage($site, $tagsPage);

    $articles = Article::factory()
        ->site($site)
        ->layout($articleLayout)
        ->withTranslations($site->languages)
        ->forEachSequence(
            ['publish_from' => '2023-01-01'],
            ['publish_from' => '2023-02-01'],
            ['publish_from' => '2023-03-01'],
        )
        ->create();

    expect($archivesPage)
        ->toBeInstanceOf(Page::class)
        ->type->name->toBe('System')
        ->layout->name->toBe('Archives')
        ->parent->name->toBe('Blog');

    get($archivesPage->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            'title',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($archivesPage->translation->meta_title),
        )
        ->assertElementExists(
            'h1',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($archivesPage->translation->title),
        )
        ->assertElementExists(
            '.widget-archives',
            fn (AssertElement $elm): BaseAssert => $elm->contains('.widget-archives-month', count: 3)
                ->each(
                    '.widget-archives-month',
                    fn (AssertElement $month, int $index): BaseAssert => $month->find(
                        'a',
                        fn (AssertElement $link): BaseAssert => $link->has(
                            'href',
                            GenerateArchiveUrl::run($archivePage->pageUrl, ArchiveMonthData::from([
                                'year' => '2023',
                                'month' => 3 - $index,
                            ])),
                        ),
                    ),
                ),
        );
});

test('archive page list articles by month/year', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $siteDomain = SiteDomain::factory()->default()->create();
    $site = $siteDomain->site;

    $blogPage = $blogCreator->createBlogPage($site);
    $archivesPage = $blogCreator->createArchivesPage($blogPage);
    $archivePage = $blogCreator->createArchivePage($archivesPage);
    $articleType = $blogCreator->createArticlePageType();
    $articleLayout = $blogCreator->createArticleLayout();

    $publishDate = CarbonImmutable::now()->subMonth();

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

    $archiveUrl = GenerateArchiveUrl::run($archivePage->pageUrl, ArchiveMonthData::fromDate($publishDate));

    expect($archivePage)
        ->toBeInstanceOf(Page::class)
        ->type->name->toBe('Archive Page')
        ->layout->name->toBe('Results')
        ->parent->name->toBe('Archives')
        ->and($archivePage->getAncestors(['name'])->pluck('name')->toArray())
        ->toEqual(['Blog', 'Archives']);

    get($archiveUrl)
        ->assertOk()
        ->assertElementExists(
            'title',
            fn (AssertElement $elm): BaseAssert => $elm->containsText(
                __($archivePage->title, ['archive_month' => $publishDate->format('F'), 'archive_year' => $publishDate->year]),
            ),
        )
        ->assertDontSeeText('no-results');
});

test('error page when no articles found for given month/year', function (string $slug): void {
    $blogCreator = resolve(BlogCreator::class);

    $siteDomain = SiteDomain::factory()->default()->create();
    $site = $siteDomain->site;

    $blogPage = $blogCreator->createBlogPage($site);
    $archivesPage = $blogCreator->createArchivesPage($blogPage);
    $archivePage = $blogCreator->createArchivePage($archivesPage);

    $archiveUrl = $archivePage->pageUrl;

    $archiveUrl = $archiveUrl->full_url . $slug;

    get($archiveUrl)
        ->assertNotFound();
})->with(['/2000-01', '/text-06']);
