<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\SeoSuite\Data\AiDiscoveryAuditData;
use Capell\SeoSuite\Data\AiDiscoveryCrawlerRuleData;
use Capell\SeoSuite\Data\AiDiscoveryPageEntryData;
use Capell\SeoSuite\Data\AiDiscoveryRenderContextData;
use Capell\SeoSuite\Enums\AiDiscoveryCrawlerDirectiveEnum;
use Capell\SeoSuite\Enums\AiDiscoveryCrawlerPurposeEnum;
use Capell\SeoSuite\Enums\AiDiscoverySnapshotKindEnum;
use Capell\SeoSuite\Enums\AiDiscoveryStatusEnum;
use Capell\SeoSuite\Models\AiDiscoveryCrawlerRule;
use Capell\SeoSuite\Models\AiDiscoveryPageProfile;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use Capell\SeoSuite\Models\AiDiscoverySnapshot;
use Composer\Autoload\ClassLoader;

$composerAutoloader = require getcwd() . '/vendor/autoload.php';

if ($composerAutoloader instanceof ClassLoader) {
    $packageRoot = dirname(__DIR__, 3);

    $composerAutoloader->addPsr4('Capell\\SeoSuite\\', $packageRoot . '/src');
    $composerAutoloader->addPsr4('Capell\\SeoSuite\\Database\\Factories\\', $packageRoot . '/database/factories');
    $composerAutoloader->addPsr4('Capell\\SeoSuite\\Tests\\', $packageRoot . '/tests');
}

function createAiDiscoveryModelLanguage(): Language
{
    return Language::query()->create([
        'name' => 'English',
        'locale' => 'en',
        'code' => 'en',
        'flag' => 'gb-eng',
        'status' => true,
        'default' => true,
        'order' => 1,
    ]);
}

it('casts ai discovery page profile fields and exposes relationships', function (): void {
    $language = createAiDiscoveryModelLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()->site($site)->withTranslations($language)->create();

    $profile = AiDiscoveryPageProfile::query()->create([
        'page_id' => $page->getKey(),
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'include_in_ai_index' => false,
        'priority' => 250,
        'last_generated_at' => now(),
    ]);

    expect($profile->include_in_ai_index)->toBeFalse()
        ->and($profile->priority)->toBe(250)
        ->and($profile->page)->toBeInstanceOf(Page::class)
        ->and($profile->site)->toBeInstanceOf(Site::class)
        ->and($profile->language)->toBeInstanceOf(Language::class)
        ->and($profile->last_generated_at)->not->toBeNull();
});

it('carries active site language and domain through render context data', function (): void {
    $language = createAiDiscoveryModelLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $siteDomain = $site->siteDomains()->first();

    $context = new AiDiscoveryRenderContextData(
        site: $site,
        language: $language,
        siteDomain: $siteDomain,
    );

    expect($context->site->is($site))->toBeTrue()
        ->and($context->language->is($language))->toBeTrue()
        ->and($context->siteDomain)->toBeInstanceOf(SiteDomain::class)
        ->and($context->domainKey())->toBe($siteDomain?->getDomainKey())
        ->and((new AiDiscoveryRenderContextData($site, $language))->domainKey())->toBe('default');
});

it('formats page entries as markdown links with stripped descriptions', function (): void {
    $entry = new AiDiscoveryPageEntryData(
        title: 'About Capell',
        url: 'https://example.test/about',
        markdownUrl: 'https://example.test/about.md',
        description: 'CMS <strong>platform</strong>',
        section: 'Company',
        priority: 100,
    );

    expect($entry->toLlmsTxtLine())->toBe('- [About Capell](https://example.test/about.md): CMS platform');
});

it('casts snapshot kind and dates', function (): void {
    $language = createAiDiscoveryModelLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();

    $snapshot = AiDiscoverySnapshot::query()->create([
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'kind' => AiDiscoverySnapshotKindEnum::LlmsTxt,
        'context_key' => 'default:site',
        'content_hash' => hash('sha256', 'content'),
        'byte_size' => 7,
        'cache_key' => 'capell-seo-suite:ai-discovery:1:default:1:llms_txt',
        'generated_at' => now(),
    ]);

    expect($snapshot->kind)->toBe(AiDiscoverySnapshotKindEnum::LlmsTxt)
        ->and($snapshot->byte_size)->toBe(7)
        ->and($snapshot->generated_at)->not->toBeNull();
});

it('casts site profile and crawler rule enum fields', function (): void {
    $language = createAiDiscoveryModelLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();

    $siteProfile = AiDiscoverySiteProfile::query()->create([
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'llms_txt_enabled' => false,
        'max_full_txt_pages' => 25,
        'max_full_txt_bytes' => 125000,
        'cache_ttl_seconds' => 900,
        'status' => AiDiscoveryStatusEnum::Disabled,
    ]);

    $crawlerRule = AiDiscoveryCrawlerRule::query()->create([
        'site_id' => $site->getKey(),
        'provider' => 'OpenAI',
        'user_agent' => 'GPTBot',
        'purpose' => AiDiscoveryCrawlerPurposeEnum::Training,
        'directive' => AiDiscoveryCrawlerDirectiveEnum::Disallow,
        'path' => '/',
        'enabled' => false,
        'crawl_delay_seconds' => 10,
    ]);

    expect($siteProfile->llms_txt_enabled)->toBeFalse()
        ->and($siteProfile->max_full_txt_pages)->toBe(25)
        ->and($siteProfile->max_full_txt_bytes)->toBe(125000)
        ->and($siteProfile->cache_ttl_seconds)->toBe(900)
        ->and($siteProfile->status)->toBe(AiDiscoveryStatusEnum::Disabled)
        ->and($crawlerRule->purpose)->toBe(AiDiscoveryCrawlerPurposeEnum::Training)
        ->and($crawlerRule->directive)->toBe(AiDiscoveryCrawlerDirectiveEnum::Disallow)
        ->and($crawlerRule->enabled)->toBeFalse()
        ->and($crawlerRule->crawl_delay_seconds)->toBe(10)
        ->and($crawlerRule->site)->toBeInstanceOf(Site::class);
});

it('exposes translated enum labels and registers ai discovery models', function (): void {
    expect(AiDiscoveryCrawlerPurposeEnum::Training->getLabel())->toBe('Training')
        ->and(AiDiscoveryCrawlerDirectiveEnum::Disallow->getLabel())->toBe('Disallow')
        ->and(AiDiscoverySnapshotKindEnum::LlmsFullTxt->getLabel())->toBe('llms-full.txt')
        ->and(AiDiscoveryStatusEnum::Fresh->getLabel())->toBe('Fresh')
        ->and(CapellCore::getModels())->toContain(AiDiscoverySiteProfile::class)
        ->and(CapellCore::getModels())->toContain(AiDiscoveryPageProfile::class)
        ->and(CapellCore::getModels())->toContain(AiDiscoveryCrawlerRule::class)
        ->and(CapellCore::getModels())->toContain(AiDiscoverySnapshot::class);
});

it('carries audit and crawler rule boundary data', function (): void {
    $audit = new AiDiscoveryAuditData(
        pageId: 123,
        checkKey: 'ai_discovery_summary',
        severity: 'warning',
        message: 'Summary missing.',
        passed: false,
    );

    $crawlerRule = new AiDiscoveryCrawlerRuleData(
        provider: 'OpenAI',
        userAgent: 'GPTBot',
        purpose: AiDiscoveryCrawlerPurposeEnum::Training,
        directive: AiDiscoveryCrawlerDirectiveEnum::Disallow,
        path: '/',
        enabled: true,
        sourceUrl: 'https://platform.openai.com/docs/bots',
        notes: 'Seeded default.',
    );

    expect($audit->pageId)->toBe(123)
        ->and($audit->passed)->toBeFalse()
        ->and($crawlerRule->userAgent)->toBe('GPTBot')
        ->and($crawlerRule->purpose)->toBe(AiDiscoveryCrawlerPurposeEnum::Training)
        ->and($crawlerRule->directive)->toBe(AiDiscoveryCrawlerDirectiveEnum::Disallow);
});
