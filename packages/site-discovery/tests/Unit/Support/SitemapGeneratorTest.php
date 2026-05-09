<?php

declare(strict_types=1);

use Capell\SiteDiscovery\Support\SitemapGenerator;
use Capell\SiteDiscovery\Tests\SiteDiscoveryTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(SiteDiscoveryTestCase::class);

beforeEach(function (): void {
    if (! Schema::hasTable('seo_suite_test_sitemap_pages')) {
        Schema::create('seo_suite_test_sitemap_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('slug');
            $table->timestamp('updated_at')->nullable();
        });
    }

    DB::table('seo_suite_test_sitemap_pages')->truncate();
});

it('builds sitemap entries from a database table', function (): void {
    DB::table('seo_suite_test_sitemap_pages')->insert([
        ['slug' => 'about', 'updated_at' => '2026-05-07 10:30:00'],
        ['slug' => '/contact', 'updated_at' => null],
    ]);

    $generator = SitemapGenerator::fromTable(
        db: DB::connection(),
        table: 'seo_suite_test_sitemap_pages',
        baseUrl: 'https://example.com/',
        changefreq: 'daily',
        priority: 0.9,
    );

    $xml = $generator->toXml();

    expect($generator->count())->toBe(2)
        ->and($xml)->toContain('<loc>https://example.com/about</loc>')
        ->and($xml)->toContain('<lastmod>2026-05-07</lastmod>')
        ->and($xml)->toContain('<loc>https://example.com/contact</loc>')
        ->and($xml)->toContain('<changefreq>daily</changefreq>')
        ->and($xml)->toContain('<priority>0.9</priority>');
});

it('normalizes invalid change frequency and clamps priority values', function (): void {
    $xml = (new SitemapGenerator)
        ->add('https://example.com/high', changefreq: 'sometimes', priority: 3.5)
        ->add('https://example.com/low', changefreq: 'never', priority: -2.0)
        ->toXml();

    expect($xml)->toContain('<loc>https://example.com/high</loc>')
        ->and($xml)->toContain('<changefreq>monthly</changefreq>')
        ->and($xml)->toContain('<priority>1.0</priority>')
        ->and($xml)->toContain('<loc>https://example.com/low</loc>')
        ->and($xml)->toContain('<changefreq>never</changefreq>')
        ->and($xml)->toContain('<priority>0.0</priority>');
});

it('writes sitemap XML to nested directories', function (): void {
    $path = storage_path('framework/testing/site-discovery/sitemaps/example.xml');

    if (file_exists($path)) {
        unlink($path);
    }

    $written = (new SitemapGenerator)
        ->add('https://example.com/page', lastmod: '2026-05-07')
        ->writeTo($path);

    expect($written)->toBeTrue()
        ->and(file_exists($path))->toBeTrue()
        ->and((string) file_get_contents($path))->toContain('<loc>https://example.com/page</loc>');
});
