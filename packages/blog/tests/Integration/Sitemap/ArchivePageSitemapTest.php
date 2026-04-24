<?php

declare(strict_types=1);

use Capell\Blog\Models\Article;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\Support\Sitemap\ArchivesSitemap;
use Capell\Core\Data\SitemapPageData;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

uses(TestingFrontend::class);

it('builds recursive sitemap for archive page with parent chain and month children', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    $domain = SiteDomain::factory()->for($site)->create();

    $blogPage = $blogCreator->createBlogPage($site);
    $archivesPage = $blogCreator->createArchivesPage($blogPage);
    $archivePage = $blogCreator->createArchivePage($archivesPage);

    Article::factory()->count(3)
        ->site($site)
        ->withTranslations($language)
        ->forEachSequence(
            ['created_at' => '2023-01-01'],
            ['created_at' => '2023-02-01'],
            ['created_at' => '2023-03-01'],
        )
        ->create();

    $archiveUrl = $archivePage->pageUrl->full_url;
    $archivesUrl = collect([
        $archiveUrl . '/2023-1',
        $archiveUrl . '/2023-2',
        $archiveUrl . '/2023-3',
    ]);

    $sitemap = new ArchivesSitemap(site: $site, domain: $domain, language: $language);
    $result = $sitemap->fetch();

    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1);

    /** @var SitemapPageData $root */
    $root = $result->first();

    /** @var SitemapPageData $archivesNode */
    $archivesNode = $root->children->first();

    expect($root)
        ->toBeInstanceOf(SitemapPageData::class)
        ->pageId->toBe($blogPage->id)
        ->lastModified->toBeInstanceOf(CarbonImmutable::class)
        ->children
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($archivesNode)
        ->pageId->toBe($archivesPage->id)
        ->lastModified->toBeInstanceOf(CarbonImmutable::class)
        ->children
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(3)
        ->and($root->toArray()['lastModified'])
        ->toBe($root->lastModified?->toAtomString())
        ->and($archivesNode->toArray()['lastModified'])
        ->toBe($archivesNode->lastModified?->toAtomString())
        ->and($archivesNode->children->pluck('url'))
        ->toContain($archivesUrl->first())
        ->toContain($archivesUrl->get(1))
        ->toContain($archivesUrl->last());
});
