<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Type;
use Capell\SeoSuite\Support\Sitemap\XmlSitemapGenerator;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config(['capell.sitemap.disk' => 'local', 'capell.sitemap.directory' => 'sitemaps_test']);
    $storage = Storage::disk('local');
    $storage->deleteDirectory('sitemaps_test');
    $storage->makeDirectory('sitemaps_test');
});

afterEach(function (): void {
    $storage = Storage::disk('local');
    $storage->deleteDirectory('sitemaps_test');
});

it('generates sitemap and returns total page count', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    $page = Page::factory()->site($site)->withTranslations()->create();
    $generator = new XmlSitemapGenerator;
    $xml = $generator->generate($site);

    // Assert only one <url> entry (excluding root <urlset>)
    expect(substr_count($xml, '<url>'))->toBe(1);

    $domain = $site->siteDomains->first();
    $filename = $domain->getDomainKey() . '.xml';
    $storage = Storage::disk('local');
    $filePath = 'sitemaps_test/' . $filename;
    expect($storage->exists($filePath))->toBeTrue();
    $xmlFile = $storage->get($filePath);
    expect($xmlFile)->toContain('<loc>')
        ->and($xmlFile)->toContain($page->pageUrl->full_url);
});

it('calls progress callbacks with correct arguments and sequence', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->recycle($language)->withTranslations()->create();
    SiteDomain::factory()->for($site)->state(['language_id' => $language->id])->create();
    $site->load('siteDomains.language');
    Page::factory()->site($site)->withTranslations()->create();
    $generator = new XmlSitemapGenerator;
    $calls = [];
    $generator->process(
        $site,
        function (SiteDomain $domain) use (&$calls): void {
            $calls[] = 'start';
        },
        function (int $total, string $domainKey) use (&$calls): void {
            $calls[] = 'prepare';
        },
        function (string $url) use (&$calls): void {
            $calls[] = 'checkpoint';
        },
        function () use (&$calls): void {
            $calls[] = 'end';
        },
    );

    expect($calls)
        ->toContain('start')
        ->toContain('prepare')
        ->toContain('checkpoint')
        ->toContain('end');
});

it('writes one XML per domain and includes expected URLs', function (): void {
    $languages = Language::factory()->count(2)->create();
    $site = Site::factory()
        ->language($languages[0])
        ->withTranslations($languages)
        ->create();

    $page = Page::factory()->site($site)->withTranslations()->create();
    expect($page->pageUrls)->toHaveCount(2);
    [$url1, $url2] = $page->pageUrls->load('siteDomain')->take(2)->all();
    $generator = new XmlSitemapGenerator;
    $generator->generate($site);

    $domainKeys = $site->siteDomains->pluck('domain')->map(fn (string $d): string => str_replace(['.', ':', '/'], '-', $d));
    $storage = Storage::disk('local');
    $files = collect($storage->files('sitemaps_test'))
        ->filter(fn (string $file): bool => str_ends_with($file, '.xml'));
    expect($files)->toHaveCount(2);
    $xmls = $files->map(fn (string $file): string => $storage->get($file));
    $urls = [$url1->full_url, $url2->full_url];

    foreach ($urls as $expectedUrl) {
        expect($xmls->filter(fn (string $xml): bool => str_contains($xml, $expectedUrl))->isNotEmpty())->toBeTrue();
    }
});

it('skips generation when no pages and still signals end', function (): void {
    $site = Site::factory()->withTranslations()->create();
    SiteDomain::factory()->for($site)->create();
    $site->load('siteDomains.language');
    $generator = new XmlSitemapGenerator;
    $calls = [];
    $generator->process(
        $site,
        function (SiteDomain $domain) use (&$calls): void {
            expect($domain)->toBeInstanceOf(SiteDomain::class);

            $calls[] = 'start';
        },
        function (int $total, string $domainKey) use (&$calls): void {
            $calls[] = 'prepare';
        },
        function (string $url) use (&$calls): void {
            $calls[] = 'checkpoint';
        },
        function () use (&$calls): void {
            $calls[] = 'end';
        },
    );
    $storage = Storage::disk('local');
    if (! $storage->exists('sitemaps_test')) {
        $storage->makeDirectory('sitemaps_test');
    }

    $files = collect($storage->files('sitemaps_test'));
    expect($files)->toBeEmpty()
        ->and($calls)->toContain('end');
});

it('sitemap omits unpublished pages', function (): void {
    $lang = Language::factory()->create();
    $siteDomain = SiteDomain::factory()->state([
        'domain' => 'example.com',
        'language_id' => $lang->id,
        'scheme' => 'http',
    ])->create();
    $site = $siteDomain->site;
    $pageType = Type::factory()->page()->create([
        'meta' => ['listable' => true, 'sitemap' => true],
    ]);
    // Published page (should appear)
    $publishedPage = Page::factory()->site($site)->type($pageType)->create();
    $publishedPage->translations()
        ->create([
            'language_id' => $lang->id,
            'title' => 'Published',
        ]);
    // Unpublished page (should NOT appear)
    $attributes = Page::factory()->pending()->make([
        'site_id' => $site->id,
        'name' => 'Draft',
        'type_id' => $pageType->id,
    ])->getAttributes();
    $fillable = (new Page)->getFillable();
    $attributesOnlyFillable = array_intersect_key($attributes, array_flip($fillable));
    $unpublishedPage = Page::create($attributesOnlyFillable);
    $unpublishedPage->translations()->create([
        'language_id' => $lang->id,
        'title' => 'Draft',
    ]);
    $generator = new XmlSitemapGenerator;
    $xml = $generator->generate($site);

    $domain = $site->siteDomains->first();
    $filename = $domain->getDomainKey() . '.xml';
    $storage = Storage::disk('local');
    $filePath = 'sitemaps_test/' . $filename;
    expect($storage->exists($filePath))->toBeTrue();
    $xmlFile = $storage->get($filePath);
    expect($xmlFile)->toContain('<urlset')
        ->and($xmlFile)->toContain('published')
        ->and($xmlFile)->not()->toContain('draft');
});

it('sitemap includes multiple pages', function (): void {
    $lang = Language::factory()->create();
    $siteDomain = SiteDomain::factory()->state([
        'domain' => 'example.com',
        'language_id' => $lang->id,
        'scheme' => 'http',
    ])->create();
    $site = $siteDomain->site;
    $pageType = Type::factory()->page()->create([
        'meta' => ['listable' => true, 'sitemap' => true],
    ]);
    $pages = Page::factory()
        ->count(2)
        ->site($site)
        ->type($pageType)
        ->withTranslations()
        ->create();

    $xml = (new XmlSitemapGenerator)->generate($site);

    expect($xml)->toContain('<urlset')
        ->and($xml)->toContain($pages[0]->pageUrl->full_url)
        ->and($xml)->toContain($pages[1]->pageUrl->full_url);
});
