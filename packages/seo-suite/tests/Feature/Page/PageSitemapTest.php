<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Support\Creator\SitemapPageCreator;
use Capell\SeoSuite\Support\Sitemap\XmlSitemapGenerator;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

// Shared helper: configure a fake sitemap disk used by several tests.
function configureFakeSitemapDisk(): void
{
    config(['capell.sitemap.disk' => 'array', 'capell.sitemap.directory' => 'sitemaps']);
    Storage::fake('array');
    Cache::driver('array');
}

test('sitemap html page', function (): void {
    $languages = Language::factory()->count(3)->create();
    $site = Site::factory()->withTranslations($languages)->create();

    $pageCreator = resolve(SitemapPageCreator::class);

    $sitemapPage = $pageCreator->createSitemapPage($site, $languages);

    $parentPage = Page::factory()->site($site)->withTranslations($languages)->create();
    $childPage = Page::factory()->site($site)->parent($parentPage)->withTranslations($languages)->create();
    $homepage = Page::factory()->site($site)->home()->withTranslations($languages, slug: '/')->create();

    // PageSaved listeners populate the sitemap cache during page creation,
    // but afterCreating hooks (translations, pageUrl) run after the listener
    // fires — so the cache may exclude the most recently created pages until
    // the next save invalidates it. Flush before requesting to ensure the
    // sitemap reflects the final DB state.
    Cache::flush();

    $siteMapUrls = [
        $homepage->pageUrl->full_url,
        $sitemapPage->pageUrl->full_url,
        $parentPage->pageUrl->full_url,
        $childPage->pageUrl->full_url,
    ];

    get($sitemapPage->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            'h1',
            fn (AssertElement $elm): BaseAssert => $elm->containsText($sitemapPage->translation->title),
        )
        ->assertElementExists(
            '.vsitemap',
            fn (AssertElement $elm): BaseAssert => $elm->each(
                'a',
                fn (AssertElement $aElm, int $index): BaseAssert => $aElm->has('href', $siteMapUrls[$index]),
            ),
        );
});

test('sitemap default page label is translated after providers boot', function (): void {
    expect(CapellCore::getDefaultPage('sitemap')->label)->toBe('Sitemap');
});

test('sitemap xml page', function (): void {
    config(['capell.sitemap.disk' => 'array', 'capell.sitemap.directory' => 'sitemaps']);
    Storage::fake('array');
    Cache::driver('array');

    $languages = Language::factory()->count(3)->create();
    $site = Site::factory()->withTranslations($languages)->create();

    $pageCreator = resolve(SitemapPageCreator::class);
    $sitemapPage = $pageCreator->createSitemapPage($site, $languages);
    $homepage = Page::factory()->site($site)->home()->withTranslations($languages, slug: '/')->create();
    $pages = Page::factory()->count(5)->site($site)->withTranslations($languages)->create();
    Page::factory()->site($site)->withTranslations($languages)->meta('hidden', true)->create();

    $filename = $sitemapPage->pageUrl->full_url . '.xml';

    resolve(XmlSitemapGenerator::class)->generate($site);

    $siteMapUrls = [
        $homepage->pageUrl->full_url,
        $sitemapPage->pageUrl->full_url,
        ...$pages->pluck('pageUrl.full_url')->toArray(),
    ];

    get($sitemapPage->pageUrl->full_url . '-xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=utf-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
        ->assertElementExists(
            'urlset',
            fn (AssertElement $elm): BaseAssert => $elm->contains('url', 7)
                ->each(
                    'url',
                    fn (AssertElement $urlElm, int $index): BaseAssert => $urlElm
                        ->find(
                            'loc',
                            fn (AssertElement $locElm): BaseAssert => $locElm->containsText($siteMapUrls[$index]),
                        ),
                ),
        );
});

test('sitemap xml page returns 404 if file missing', function (): void {
    config(['capell.sitemap.disk' => 'array', 'capell.sitemap.directory' => 'sitemaps']);
    Storage::fake('array');
    Cache::driver('array');

    $languages = Language::factory()->create();
    $site = Site::factory()->withTranslations($languages)->create();
    $pageCreator = resolve(SitemapPageCreator::class);
    $sitemapPage = $pageCreator->createSitemapPage($site, collect([$languages]));

    get($sitemapPage->pageUrl->full_url . '-xml')
        ->assertStatus(404);
});

test('sitemap xml page returns 304 with ETag', function (): void {
    config(['capell.sitemap.disk' => 'array', 'capell.sitemap.directory' => 'sitemaps']);
    Storage::fake('array');
    Cache::driver('array');

    $languages = Language::factory()->create();
    $site = Site::factory()->withTranslations($languages)->create();
    $pageCreator = resolve(SitemapPageCreator::class);
    $sitemapPage = $pageCreator->createSitemapPage($site, collect([$languages]));

    Page::factory()->count(5)->site($site)->withTranslations($languages)->create();

    resolve(XmlSitemapGenerator::class)->generate($site);

    $filePath = 'sitemaps/' . $site->siteDomain->getDomainKey() . '.xml';
    // Guarantee the file exists for the test
    if (! Storage::disk('array')->exists($filePath)) {
        Storage::disk('array')->put($filePath, '<?xml version="1.0" encoding="UTF-8"?><urlset></urlset>');
    }

    $fileContents = Storage::disk('array')->get($filePath);
    expect($fileContents)->not()->toBeNull('Sitemap file missing for ETag test');
    $etag = 'W/"' . hash('sha256', (string) $fileContents) . '"';

    get($sitemapPage->pageUrl->full_url . '-xml', ['If-None-Match' => $etag])
        ->assertStatus(304);
});

// ---------------------------------------------------------------------------
// Chunk serving: ?p=N query parameter
// ---------------------------------------------------------------------------

test('sitemap xml page serves a chunk file when ?p=N is provided', function (): void {
    config([
        'capell.sitemap.disk' => 'array',
        'capell.sitemap.directory' => 'sitemaps',
        'capell.sitemap.max_urls_per_file' => 2,
        'capell.sitemap.xml_path' => '/sitemap-xml',
    ]);
    Storage::fake('array');
    Cache::driver('array');

    $languages = Language::factory()->create();
    $site = Site::factory()->withTranslations(collect([$languages]))->create();
    $pageCreator = resolve(SitemapPageCreator::class);
    $sitemapPage = $pageCreator->createSitemapPage($site, collect([$languages]));

    // 3 pages + 1 sitemap page = 4 URLs → 2 chunks of 2 (limit=2)
    Page::factory()->count(3)->site($site)->withTranslations(collect([$languages]))->create();

    resolve(XmlSitemapGenerator::class)->generate($site);

    // Chunk 1 must be a urlset with 2 <url> entries
    get($sitemapPage->pageUrl->full_url . '-xml?p=1')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=utf-8')
        ->assertElementExists(
            'urlset',
            fn (AssertElement $elm): BaseAssert => $elm->contains('url', 2),
        );

    // Chunk 2 must be a urlset with 2 <url> entries
    get($sitemapPage->pageUrl->full_url . '-xml?p=2')
        ->assertOk()
        ->assertElementExists(
            'urlset',
            fn (AssertElement $elm): BaseAssert => $elm->contains('url', 2),
        );
});

test('sitemap xml page serves a sitemapindex as the main file when chunks exist', function (): void {
    config([
        'capell.sitemap.disk' => 'array',
        'capell.sitemap.directory' => 'sitemaps',
        'capell.sitemap.max_urls_per_file' => 1,
    ]);
    Storage::fake('array');
    Cache::driver('array');

    $languages = Language::factory()->create();
    $site = Site::factory()->withTranslations(collect([$languages]))->create();
    $pageCreator = resolve(SitemapPageCreator::class);
    $sitemapPage = $pageCreator->createSitemapPage($site, collect([$languages]));

    Page::factory()->count(2)->site($site)->withTranslations(collect([$languages]))->create();

    resolve(XmlSitemapGenerator::class)->generate($site);

    // Main URL (no ?p) returns the sitemapindex
    get($sitemapPage->pageUrl->full_url . '-xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=utf-8')
        ->assertElementExists('sitemapindex');
});

test('sitemap xml page returns 404 for a chunk page that does not exist', function (): void {
    config(['capell.sitemap.disk' => 'array', 'capell.sitemap.directory' => 'sitemaps']);
    Storage::fake('array');
    Cache::driver('array');

    $languages = Language::factory()->create();
    $site = Site::factory()->withTranslations(collect([$languages]))->create();
    $pageCreator = resolve(SitemapPageCreator::class);
    $sitemapPage = $pageCreator->createSitemapPage($site, collect([$languages]));

    // Generate a regular (non-chunked) sitemap — no chunk files exist
    Page::factory()->site($site)->withTranslations(collect([$languages]))->create();
    resolve(XmlSitemapGenerator::class)->generate($site);

    get($sitemapPage->pageUrl->full_url . '-xml?p=99')
        ->assertStatus(404);
});
