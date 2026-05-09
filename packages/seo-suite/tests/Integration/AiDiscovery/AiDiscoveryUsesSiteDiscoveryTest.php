<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Actions\GenerateLlmsTxtAction;
use Capell\SeoSuite\Data\AiDiscoveryRenderContextData;
use Capell\SiteDiscovery\Actions\DiscoverPublicPagesAction;
use Capell\SiteDiscovery\Data\DiscoverablePageData;
use Composer\Autoload\ClassLoader;
use Illuminate\Support\Facades\Cache;

$composerAutoloader = require getcwd() . '/vendor/autoload.php';

if ($composerAutoloader instanceof ClassLoader) {
    $packageRoot = dirname(__DIR__, 3);

    $composerAutoloader->addPsr4('Capell\\SeoSuite\\', $packageRoot . '/src');
    $composerAutoloader->addPsr4('Capell\\SeoSuite\\Database\\Factories\\', $packageRoot . '/database/factories');
    $composerAutoloader->addPsr4('Capell\\SeoSuite\\Tests\\', $packageRoot . '/tests');
}

beforeEach(function (): void {
    Cache::flush();
});

it('excludes page meta noindex pages from site discovery and llms txt', function (): void {
    $language = Language::query()->create([
        'name' => 'English',
        'locale' => 'en',
        'code' => 'en',
        'flag' => 'gb-eng',
        'status' => true,
        'default' => true,
        'order' => 1,
    ]);
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

    $pages = DiscoverPublicPagesAction::run($site, $language);
    $llmsTxt = GenerateLlmsTxtAction::run($site, $language);
    $contextLlmsTxt = GenerateLlmsTxtAction::run(new AiDiscoveryRenderContextData($site, $language, $site->siteDomains()->first()));

    expect($pages->map(fn (DiscoverablePageData $data): ?int => $data->page?->getKey())->all())->toBe([$publicPage->getKey()])
        ->and($llmsTxt)->toContain('Public Page')
        ->and($llmsTxt)->not->toContain('Private Page')
        ->and($contextLlmsTxt)->toContain('Public Page')
        ->and($contextLlmsTxt)->not->toContain('Private Page');
});
