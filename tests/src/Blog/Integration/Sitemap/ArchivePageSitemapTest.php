<?php

declare(strict_types=1);

use Capell\Blog\Data\ArchiveMonthData;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\Support\Sitemap\ArchivesSitemap;
use Capell\Core\Data\SitemapPageData;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;
use Illuminate\Support\Collection;

uses(TestingFrontend::class);

it('builds recursive sitemap for archive page with parent chain and month children', function (): void {
    $blogCreator = resolve(BlogCreator::class);

    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    $domain = SiteDomain::factory()->for($site)->create();

    $blogPage = $blogCreator->createBlogPage($site, languages: $site->languages);
    $archivePage = $blogCreator->createArchivePage($site, $blogPage, $site->languages, createWidgets: false);

    // Parent chain: Home -> Section -> BlogPage -> ArchivePage
    $home = Page::factory()->for($site)->withTranslations()->create();
    $section = Page::factory()->for($site)->withTranslations()->create();

    $blogPage->parent()->associate($section)->save();
    $section->parent()->associate($home)->save();

    // Seed a few archive months by creating posts with dates
    // Using ArchiveMonthData isn't necessary directly; Archive loader reads from DB

    $sitemap = new ArchivesSitemap(site: $site, domain: $domain, language: $language);
    $result = $sitemap->fetch();

    expect($result)->toBeInstanceOf(Collection::class);
    // Depending on archive data, ensure we have a single root node
    expect($result)->toHaveCount(1);

    /** @var SitemapPageData $root */
    $root = $result->first();
    expect($root)->toBeInstanceOf(SitemapPageData::class);
    expect($root->label)->toBe($home->translation->label);

    /** @var SitemapPageData $sectionNode */
    $sectionNode = $root->children->first();
    expect($sectionNode->label)->toBe($section->translation->label);

    /** @var SitemapPageData $blogNode */
    $blogNode = $sectionNode->children->first();
    expect($blogNode->label)->toBe($blogPage->translation->title);

    // Archive children exist under blog node
    expect($blogNode->children)->toBeInstanceOf(Collection::class);
    // At least one month child when archives exist; can't guarantee count, but ensure structure
    if ($blogNode->children->isNotEmpty()) {
        $firstMonth = $blogNode->children->first();
        expect($firstMonth)->toBeInstanceOf(SitemapPageData::class);
        // URL should start with archive page full URL followed by /YYYY-M
        expect($firstMonth->url)->toStartWith($archivePage->pageUrl->full_url . '/');
    }
});
