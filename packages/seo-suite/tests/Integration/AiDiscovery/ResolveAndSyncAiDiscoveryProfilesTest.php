<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Actions\ResolveAiDiscoveryProfileAction;
use Capell\SeoSuite\Actions\SyncAiDiscoveryPageProfilesAction;
use Capell\SeoSuite\Enums\AiDiscoveryStatusEnum;
use Capell\SeoSuite\Models\AiDiscoveryPageProfile;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use Capell\Tests\AbstractTestCase;
use Composer\Autoload\ClassLoader;
use Livewire\LivewireServiceProvider;

$composerAutoloader = require getcwd() . '/vendor/autoload.php';

if ($composerAutoloader instanceof ClassLoader) {
    $packageRoot = dirname(__DIR__, 3);

    $composerAutoloader->addPsr4('Capell\\SeoSuite\\', $packageRoot . '/src');
    $composerAutoloader->addPsr4('Capell\\SeoSuite\\Database\\Factories\\', $packageRoot . '/database/factories');
}

class ResolveAndSyncAiDiscoveryProfilesTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-seo-suite';
    }

    /**
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getDefaultPackageProviders(),
            LivewireServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::registerPackage(
            'capell-app/seo-suite',
            path: dirname(__DIR__, 3),
        );
        CapellCore::forcePackageInstalled('capell-app/seo-suite');
    }
}

uses(ResolveAndSyncAiDiscoveryProfilesTestCase::class);

it('resolves site profile defaults for a site and language', function (): void {
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

    $profile = ResolveAiDiscoveryProfileAction::run($site, $language);

    expect($profile)->toBeInstanceOf(AiDiscoverySiteProfile::class)
        ->and($profile->site_id)->toBe($site->getKey())
        ->and($profile->language_id)->toBe($language->getKey())
        ->and($profile->llms_txt_enabled)->toBeTrue()
        ->and($profile->llms_full_txt_enabled)->toBeFalse()
        ->and($profile->markdown_pages_enabled)->toBeTrue()
        ->and($profile->accept_markdown_enabled)->toBeFalse()
        ->and($profile->default_include_pages)->toBeTrue()
        ->and($profile->max_full_txt_pages)->toBe(50)
        ->and($profile->max_full_txt_bytes)->toBe(250000)
        ->and($profile->cache_ttl_seconds)->toBe(3600)
        ->and($profile->default_section)->toBe('Pages')
        ->and($profile->status)->toBe(AiDiscoveryStatusEnum::Enabled);
});

it('resolves a page profile using site profile defaults', function (): void {
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
    $page = Page::factory()->site($site)->withTranslations($language)->create();

    $profile = ResolveAiDiscoveryProfileAction::run($site, $language, $page);

    expect($profile)->toBeInstanceOf(AiDiscoveryPageProfile::class)
        ->and($profile->page_id)->toBe($page->getKey())
        ->and($profile->site_id)->toBe($site->getKey())
        ->and($profile->language_id)->toBe($language->getKey())
        ->and($profile->include_in_ai_index)->toBeTrue()
        ->and($profile->section)->toBe('Pages')
        ->and($profile->priority)->toBe(500);
});

it('syncs sitemap eligible pages into ai discovery page profiles', function (): void {
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
    $publicPage = Page::factory()->site($site)->withTranslations($language, ['title' => 'Public'])->create();

    Page::factory()
        ->site($site)
        ->withTranslations($language, ['title' => 'Private'])
        ->meta('robots', ['noindex'])
        ->create();

    AiDiscoverySiteProfile::query()->create([
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'default_include_pages' => false,
        'default_section' => 'Knowledge Base',
    ]);

    $profiles = SyncAiDiscoveryPageProfilesAction::run($site, $language);

    expect($profiles)->toHaveCount(1)
        ->and($profiles->first())->toBeInstanceOf(AiDiscoveryPageProfile::class)
        ->and($profiles->first()->page_id)->toBe($publicPage->getKey())
        ->and($profiles->first()->include_in_ai_index)->toBeFalse()
        ->and($profiles->first()->section)->toBe('Knowledge Base')
        ->and($profiles->first()->priority)->toBe(500)
        ->and(AiDiscoveryPageProfile::query()->where('page_id', $publicPage->getKey())->exists())->toBeTrue()
        ->and(AiDiscoveryPageProfile::query()->count())->toBe(1);
});
