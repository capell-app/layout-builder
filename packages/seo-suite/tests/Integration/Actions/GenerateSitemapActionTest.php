<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\SeoSuite\Actions\GenerateSitemapAction;
use Illuminate\Support\Facades\Storage;

it('handles the sitemap generation', function (): void {
    $storage = Storage::fake(config('capell.sitemap.disk'));

    // Arrange
    $langauge = Language::factory()->create();
    $site = Site::factory()
        ->recycle($langauge)
        ->hasSiteDomain()
        ->create();

    Page::factory()
        ->count(5)
        ->site($site)
        ->withTranslations($site->languages)
        ->create();

    // Act
    $xml = GenerateSitemapAction::run($site);

    // Assert
    expect($site->siteDomains)->toHaveCount(1)
        ->and($xml)->toBeString()
        ->and($storage->exists(config('capell.sitemap.directory')))->toBeTrue();

    $dir = config('capell.sitemap.directory');

    $site->siteDomains->each(function (SiteDomain $domain) use ($dir, $storage): void {
        $filename = $domain->getDomainKey() . '.xml';
        $storage->assertExists($dir . ('/' . $filename));
    });
});
