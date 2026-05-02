<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoTools\Actions\GenerateLlmsTxtAction;
use Capell\SeoTools\Support\Sitemap\Queries\PagesForSitemap;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

it('excludes page meta noindex pages from sitemap page discovery and llms txt', function (): void {
    $language = Language::factory()->state(['locale' => 'en'])->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();

    $publicPage = Page::factory()
        ->site($site)
        ->withTranslations($language, ['title' => 'Public Page'])
        ->create();

    Page::factory()
        ->site($site)
        ->withTranslations($language, ['title' => 'Private Page'])
        ->meta('robots', ['noindex'])
        ->create();

    $pages = resolve(PagesForSitemap::class)->get($site, $language);
    $llmsTxt = GenerateLlmsTxtAction::run($site, $language);

    expect($pages->pluck('id')->all())->toBe([$publicPage->getKey()])
        ->and($llmsTxt)->toContain('Public Page')
        ->and($llmsTxt)->not->toContain('Private Page');
});
