<?php

declare(strict_types=1);

use Capell\Blog\Data\ArchiveMonthData;
use Capell\Blog\Models\Article;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\Support\StaticSite\BlogStaticSiteExtension;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

it('generates archive URLs for static site', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $archiveDate = CarbonImmutable::now()->subMonths(2);

    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    $domain = SiteDomain::factory()->language($language)->for($site)->create();

    $articles = Article::factory()
        ->count(2)
        ->site($site)
        ->withTranslations()
        ->state([
            'visible_from' => $archiveDate,
        ])
        ->create();

    $blogPage = $blogCreator->createBlogPage($site);

    $archivesPage = $blogCreator->createArchivesPage($blogPage);
    $archivePage = $blogCreator->createArchivePage($archivesPage);
    $archiveUrl = rtrim($archivePage->pageUrl->url, '/*') . '/';

    // Fake HTTP responses for all expected URLs
    $httpFakes = [];

    $archiveMonth = ArchiveMonthData::fromDate($archiveDate);
    $httpFakes[$archiveUrl . $archiveMonth->year . '/' . str_pad((string) $archiveMonth->month, 2, '0', STR_PAD_LEFT)] = Http::response('ok', 200);
    Http::fake($httpFakes);

    $visited = [];
    $extension = new BlogStaticSiteExtension;
    $extension($site, $domain, function (string $url) use (&$visited): void {
        $visited[] = $url;
    });

    $expectedUrls = [
        $archiveUrl . $archiveMonth->year . '/' . str_pad((string) $archiveMonth->month, 2, '0', STR_PAD_LEFT),
    ];

    expect($visited)->not()->toBeEmpty();

    foreach ($expectedUrls as $expectedUrl) {
        expect($visited)->toContain($expectedUrl);
    }
});
