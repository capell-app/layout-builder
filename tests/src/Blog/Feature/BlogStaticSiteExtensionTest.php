<?php

declare(strict_types=1);

use Capell\Blog\Data\ArchiveMonthData;
use Capell\Blog\Enums\TagTypeEnum;
use Capell\Blog\Models\Article;
use Capell\Blog\Models\Tag;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\Support\StaticSite\BlogStaticSiteExtension;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

it('generates tag and archive URLs for static site', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $archiveDate = CarbonImmutable::now()->subMonths(2);

    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    $domain = SiteDomain::factory()->language($language)->for($site)->create();

    $tags = Tag::factory()->count(2)
        ->translate($language)
        ->type(TagTypeEnum::Page)
        ->create();

    $articles = Article::factory()
        ->count(2)
        ->site($site)
        ->withTranslations()
        ->hasAttached($tags)
        ->state([
            'publish_from' => $archiveDate,
        ])
        ->create();

    $blogPage = $blogCreator->createBlogPage($site);

    $tagsPage = $blogCreator->createTagsPage($site, $blogPage, createWidgets: true);
    $tagPage = $blogCreator->createTagPage($site, $tagsPage);
    $tagPageUrl = rtrim($tagPage->pageUrl->url, '/*') . '/';

    $archivesPage = $blogCreator->createArchivesPage($tagsPage);
    $archivePage = $blogCreator->createArchivePage($archivesPage);
    $archivePageUrl = rtrim($archivePage->pageUrl->url, '/*') . '/';

    $tagSlugs = $tags->map(fn (Tag $tag): mixed => $tag->getTranslation('slug', $language->code))->values();

    // Fake HTTP responses for all expected URLs
    $httpFakes = [];
    foreach ($tagSlugs as $slug) {
        $httpFakes[$tagPageUrl . $slug] = Http::response('ok', 200);
    }

    $archiveMonth = ArchiveMonthData::fromDate($archiveDate);
    $httpFakes[$archivePageUrl . $archiveMonth->year . '/' . str_pad((string) $archiveMonth->month, 2, '0', STR_PAD_LEFT)] = Http::response('ok', 200);
    Http::fake($httpFakes);

    $visited = [];
    $extension = new BlogStaticSiteExtension;
    $extension($site, $domain, function (string $url) use (&$visited): void {
        $visited[] = $url;
    });

    $expectedUrls = $tagSlugs->map(fn (string $slug): string => $tagPageUrl . $slug)->all();
    if ($archiveMonth) {
        $expectedUrls[] = $archivePageUrl . $archiveMonth->year . '/' . str_pad((string) $archiveMonth->month, 2, '0', STR_PAD_LEFT);
    }

    expect($visited)->not()->toBeEmpty()
        ->and($tagSlugs)->not()->toBeEmpty();

    foreach ($expectedUrls as $expectedUrl) {
        expect($visited)->toContain($expectedUrl);
    }
});
