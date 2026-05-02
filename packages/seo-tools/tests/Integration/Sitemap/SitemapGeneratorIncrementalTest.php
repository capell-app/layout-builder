<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Type;
use Capell\SeoTools\Support\Sitemap\XmlSitemapGenerator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config([
        'capell.sitemap.disk' => 'local',
        'capell.sitemap.directory' => 'sitemaps_test_inc',
        'capell.sitemap.max_urls_per_file' => 50000,
        'capell.sitemap.xml_path' => '/sitemap-xml',
    ]);
    $storage = Storage::disk('local');
    $storage->deleteDirectory('sitemaps_test_inc');
    $storage->makeDirectory('sitemaps_test_inc');
});

afterEach(function (): void {
    Storage::disk('local')->deleteDirectory('sitemaps_test_inc');
});

// ---------------------------------------------------------------------------
// Incremental: first run always regenerates
// ---------------------------------------------------------------------------

it('incremental run always regenerates when no prior state exists', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    Page::factory()->site($site)->withTranslations()->create();

    $regenerated = null;
    (new XmlSitemapGenerator)->processIncremental(
        site: $site,
        end: function (int $total, string $filePath, bool $r) use (&$regenerated): void {
            $regenerated = $r;
        },
    );

    expect($regenerated)->toBeTrue();
});

// ---------------------------------------------------------------------------
// Incremental: second run with identical pages is skipped
// ---------------------------------------------------------------------------

it('incremental run skips domain when no pages changed since last run', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    Page::factory()->site($site)->withTranslations()->create();

    $generator = new XmlSitemapGenerator;

    // First run — builds baseline state
    $generator->processIncremental(site: $site);

    // Second run — nothing changed
    $regenerated = null;
    $generator->processIncremental(
        site: $site,
        end: function (int $total, string $filePath, bool $r) use (&$regenerated): void {
            $regenerated = $r;
        },
    );

    expect($regenerated)->toBeFalse();
});

// ---------------------------------------------------------------------------
// Incremental: regenerates when a page is added
// ---------------------------------------------------------------------------

it('incremental run regenerates when a new page is added', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    Page::factory()->site($site)->withTranslations()->create();

    $generator = new XmlSitemapGenerator;
    $generator->processIncremental(site: $site);

    // Add another page and clear the sitemap page cache so the new page is visible
    Page::factory()->site($site)->withTranslations()->create();
    Cache::flush();

    $regenerated = null;
    $generator->processIncremental(
        site: $site,
        end: function (int $total, string $filePath, bool $r) use (&$regenerated): void {
            $regenerated = $r;
        },
    );

    expect($regenerated)->toBeTrue();
});

// ---------------------------------------------------------------------------
// Incremental: regenerates when a page's lastmod changes
// ---------------------------------------------------------------------------

it('incremental run regenerates when a page lastmod changes', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    $page = Page::factory()->site($site)->withTranslations()->create();

    $generator = new XmlSitemapGenerator;
    $generator->processIncremental(site: $site);

    // Bump the page's visible_from timestamp (which drives lastmod) and clear cache
    $page->update(['visible_from' => now()->addDay()]);
    Cache::flush();

    $regenerated = null;
    $generator->processIncremental(
        site: $site,
        end: function (int $total, string $filePath, bool $r) use (&$regenerated): void {
            $regenerated = $r;
        },
    );

    expect($regenerated)->toBeTrue();
});

// ---------------------------------------------------------------------------
// Incremental: end callback receives correct total and file path
// ---------------------------------------------------------------------------

it('incremental end callback receives total URL count and file path', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    Page::factory()->count(3)->site($site)->withTranslations()->create();

    $endTotal = null;
    $endPath = null;

    (new XmlSitemapGenerator)->processIncremental(
        site: $site,
        end: function (int $total, string $filePath, bool $r) use (&$endTotal, &$endPath): void {
            $endTotal = $total;
            $endPath = $filePath;
        },
    );

    expect($endTotal)->toBe(3)
        ->and($endPath)->toEndWith('.xml');
});

// ---------------------------------------------------------------------------
// Incremental: state file is saved on first run and used on second
// ---------------------------------------------------------------------------

it('state file is written after incremental run and cleaned up by delete()', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    Page::factory()->site($site)->withTranslations()->create();

    $generator = new XmlSitemapGenerator;
    $generator->processIncremental(site: $site);

    $domain = $site->siteDomains->first();
    $stateFile = 'sitemaps_test_inc/.state/' . $domain->getDomainKey() . '.json';
    expect(Storage::disk('local')->exists($stateFile))->toBeTrue();

    // delete() must remove state alongside the XML
    $generator->delete($site);
    expect(Storage::disk('local')->exists($stateFile))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Full generate() saves state for subsequent incremental runs
// ---------------------------------------------------------------------------

it('full generate() saves a state baseline so the next incremental run can skip', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    Page::factory()->site($site)->withTranslations()->create();

    $generator = new XmlSitemapGenerator;
    $generator->generate($site);

    $regenerated = null;
    $generator->processIncremental(
        site: $site,
        end: function (int $total, string $filePath, bool $r) use (&$regenerated): void {
            $regenerated = $r;
        },
    );

    expect($regenerated)->toBeFalse();
});

// ---------------------------------------------------------------------------
// Sitemap index: single file when at or below the limit
// ---------------------------------------------------------------------------

it('writes a single urlset file when URLs do not exceed max_urls_per_file', function (): void {
    config(['capell.sitemap.max_urls_per_file' => 100]);

    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    Page::factory()->count(3)->site($site)->withTranslations()->create();

    (new XmlSitemapGenerator)->generate($site);

    $domain = $site->siteDomains->first();
    $storage = Storage::disk('local');
    $mainFile = 'sitemaps_test_inc/' . $domain->getDomainKey() . '.xml';

    expect($storage->exists($mainFile))->toBeTrue();

    $xml = $storage->get($mainFile);
    expect($xml)->toContain('<urlset')
        ->and($xml)->not()->toContain('<sitemapindex');

    // No chunk files
    $chunkFiles = collect($storage->files('sitemaps_test_inc'))
        ->filter(fn (string $f): bool => (bool) preg_match('/-p\d+\.xml$/', basename($f)));
    expect($chunkFiles)->toBeEmpty();
});

// ---------------------------------------------------------------------------
// Sitemap index: splits into chunks + index when limit is exceeded
// ---------------------------------------------------------------------------

it('writes chunk files and a sitemapindex when URLs exceed max_urls_per_file', function (): void {
    config(['capell.sitemap.max_urls_per_file' => 2]);

    $language = Language::factory()->create();
    $siteDomain = SiteDomain::factory()->state([
        'domain' => 'example.com',
        'language_id' => $language->id,
        'scheme' => 'https',
        'path' => null,
    ])->create();
    $site = $siteDomain->site;
    $pageType = Type::factory()->page()->create(['meta' => ['listable' => true, 'sitemap' => true]]);
    Page::factory()->count(5)->site($site)->type($pageType)->withTranslations()->create();

    (new XmlSitemapGenerator)->generate($site);

    $storage = Storage::disk('local');
    $domainKey = $siteDomain->getDomainKey();

    // Main file must be a sitemapindex
    $mainFile = 'sitemaps_test_inc/' . $domainKey . '.xml';
    expect($storage->exists($mainFile))->toBeTrue();
    $indexXml = $storage->get($mainFile);
    expect($indexXml)->toContain('<sitemapindex')
        ->and($indexXml)->not()->toContain('<urlset');

    // Expect 3 chunks: pages 1–2, 3–4, 5
    $chunkFiles = collect($storage->files('sitemaps_test_inc'))
        ->filter(fn (string $f): bool => str_starts_with(basename($f), $domainKey . '-p') && str_ends_with($f, '.xml'));
    expect($chunkFiles)->toHaveCount(3);

    // Each chunk is a valid urlset
    foreach ($chunkFiles as $chunkFile) {
        $chunkXml = $storage->get($chunkFile);
        expect($chunkXml)->toContain('<urlset')
            ->and($chunkXml)->not()->toContain('<sitemapindex');
    }
});

it('sitemapindex <loc> entries use the configured xml_path and ?p=N query parameter', function (): void {
    config([
        'capell.sitemap.max_urls_per_file' => 2,
        'capell.sitemap.xml_path' => '/sitemap-xml',
    ]);

    $language = Language::factory()->create();
    $siteDomain = SiteDomain::factory()->state([
        'domain' => 'example.com',
        'language_id' => $language->id,
        'scheme' => 'https',
        'path' => null,
    ])->create();
    $site = $siteDomain->site;
    $pageType = Type::factory()->page()->create(['meta' => ['listable' => true, 'sitemap' => true]]);
    Page::factory()->count(3)->site($site)->type($pageType)->withTranslations()->create();

    (new XmlSitemapGenerator)->generate($site);

    $mainXml = Storage::disk('local')->get('sitemaps_test_inc/' . $siteDomain->getDomainKey() . '.xml');

    expect($mainXml)
        ->toContain('https://example.com/sitemap-xml?p=1')
        ->toContain('https://example.com/sitemap-xml?p=2');
});

// ---------------------------------------------------------------------------
// delete() removes chunk files alongside the main file
// ---------------------------------------------------------------------------

it('delete() removes all chunk files for a domain', function (): void {
    config(['capell.sitemap.max_urls_per_file' => 1]);

    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    Page::factory()->count(3)->site($site)->withTranslations()->create();

    $generator = new XmlSitemapGenerator;
    $generator->generate($site);

    $domain = $site->siteDomains->first();
    $domainKey = $domain->getDomainKey();
    $storage = Storage::disk('local');

    $filesBefore = collect($storage->files('sitemaps_test_inc'))
        ->filter(fn (string $f): bool => str_starts_with(basename($f), $domainKey) && str_ends_with($f, '.xml'));
    expect($filesBefore->count())->toBeGreaterThan(1);

    $generator->delete($site);

    $filesAfter = collect($storage->files('sitemaps_test_inc'))
        ->filter(fn (string $f): bool => str_starts_with(basename($f), $domainKey) && str_ends_with($f, '.xml'));
    expect($filesAfter)->toBeEmpty();
});
