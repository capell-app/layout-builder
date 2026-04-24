<?php

declare(strict_types=1);

use Capell\SeoTools\Support\SitemapGenerator;
use Illuminate\Database\Capsule\Manager;

test('add() accumulates URLs and count() returns the correct count', function (): void {
    $sitemap = new SitemapGenerator;

    expect($sitemap->count())->toBe(0);

    $sitemap->add('https://example.com/', changefreq: 'daily', priority: 1.0)
        ->add('https://example.com/about', priority: 0.8)
        ->add('https://example.com/blog', changefreq: 'weekly', priority: 0.7);

    expect($sitemap->count())->toBe(3);
});

test('toXml() produces valid XML with correct urlset structure', function (): void {
    $sitemap = new SitemapGenerator;
    $sitemap->add('https://example.com/');

    $xml = $sitemap->toXml();

    expect($xml)->toContain('<?xml version="1.0"');
    expect($xml)->toContain('xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"');
    expect($xml)->toContain('<urlset');
    expect($xml)->toContain('<url>');
    expect($xml)->toContain('</urlset>');
});

test('toXml() includes loc, priority, changefreq, and lastmod when provided', function (): void {
    $sitemap = new SitemapGenerator;
    $sitemap->add(
        url: 'https://example.com/page',
        lastmod: '2024-06-01',
        changefreq: 'weekly',
        priority: 0.9,
    );

    $xml = $sitemap->toXml();

    expect($xml)
        ->toContain('<loc>https://example.com/page</loc>')
        ->toContain('<lastmod>2024-06-01</lastmod>')
        ->toContain('<changefreq>weekly</changefreq>')
        ->toContain('<priority>0.9</priority>');
});

test('writeTo() writes to a temp file correctly', function (): void {
    $sitemap = new SitemapGenerator;
    $sitemap->add('https://example.com/', priority: 1.0);

    $tempPath = sys_get_temp_dir() . '/sitemap_test_' . uniqid() . '.xml';

    $result = $sitemap->writeTo($tempPath);

    expect($result)->toBeTrue();
    expect(file_exists($tempPath))->toBeTrue();

    $contents = file_get_contents($tempPath);
    expect($contents)->toContain('<loc>https://example.com/</loc>');

    unlink($tempPath);
});

test('writeTo() creates nested directories when they do not exist', function (): void {
    $sitemap = new SitemapGenerator;
    $sitemap->add('https://example.com/', priority: 1.0);

    $nestedDir = sys_get_temp_dir() . '/sitemap_nested_' . uniqid() . '/sub/dir';
    $tempPath = $nestedDir . '/sitemap.xml';

    $result = $sitemap->writeTo($tempPath);

    expect($result)->toBeTrue();
    expect(is_dir($nestedDir))->toBeTrue();
    expect(file_exists($tempPath))->toBeTrue();

    $contents = file_get_contents($tempPath);
    expect($contents)->toContain('<loc>https://example.com/</loc>');

    unlink($tempPath);
    rmdir($nestedDir);
    rmdir(dirname($nestedDir));
    rmdir(dirname($nestedDir, 2));
});

test('fromTable() reads slugs from a database table and adds them as urls', function (): void {
    $capsule = new Manager;
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    Manager::schema()->create('sitemap_pages', function ($table): void {
        $table->increments('id');
        $table->string('slug');
        $table->timestamp('updated_at')->nullable();
    });

    Manager::table('sitemap_pages')->insert([
        ['slug' => 'about', 'updated_at' => '2026-01-01 00:00:00'],
        ['slug' => 'contact', 'updated_at' => null],
    ]);

    $generator = SitemapGenerator::fromTable(
        db: Manager::connection(),
        table: 'sitemap_pages',
        baseUrl: 'https://example.com',
        slugColumn: 'slug',
        updatedAtColumn: 'updated_at',
    );

    $xml = $generator->toXml();
    expect($xml)->toContain('https://example.com/about');
    expect($xml)->toContain('https://example.com/contact');
    expect($xml)->toContain('2026-01-01');
});
