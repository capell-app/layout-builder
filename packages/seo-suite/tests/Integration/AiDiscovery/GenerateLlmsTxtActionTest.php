<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Http\Controllers\PageController as FrontendPageController;
use Capell\Frontend\Support\State\FrontendState;
use Capell\SeoSuite\Actions\BuildAiReadinessAuditAction;
use Capell\SeoSuite\Actions\BuildAiRobotsTxtRulesAction;
use Capell\SeoSuite\Actions\ClearAiDiscoveryCacheAction;
use Capell\SeoSuite\Actions\GenerateLlmsFullTxtAction;
use Capell\SeoSuite\Actions\GenerateLlmsTxtAction;
use Capell\SeoSuite\Actions\GeneratePageMarkdownAction;
use Capell\SeoSuite\Actions\PersistAiDiscoverySnapshotAction;
use Capell\SeoSuite\Actions\ResolveAiDiscoveryProfileAction;
use Capell\SeoSuite\Actions\SeedDefaultAiCrawlerRulesAction;
use Capell\SeoSuite\Actions\SyncAiDiscoveryPageProfilesAction;
use Capell\SeoSuite\Data\AiDiscoveryRenderContextData;
use Capell\SeoSuite\Enums\AiDiscoverySnapshotKindEnum;
use Capell\SeoSuite\Enums\AiDiscoveryStatusEnum;
use Capell\SeoSuite\Http\Controllers\LlmsFullTxtController;
use Capell\SeoSuite\Http\Controllers\LlmsTxtController;
use Capell\SeoSuite\Http\Controllers\PageMarkdownController;
use Capell\SeoSuite\Http\Controllers\RobotsTxtController;
use Capell\SeoSuite\Models\AiDiscoveryCrawlerRule;
use Capell\SeoSuite\Models\AiDiscoveryPageProfile;
use Capell\SeoSuite\Models\AiDiscoverySnapshot;
use Composer\Autoload\ClassLoader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$composerAutoloader = require getcwd() . '/vendor/autoload.php';

if ($composerAutoloader instanceof ClassLoader) {
    $packageRoot = dirname(__DIR__, 3);

    $composerAutoloader->addPsr4('Capell\\SeoSuite\\', $packageRoot . '/src');
    $composerAutoloader->addPsr4('Capell\\SeoSuite\\Database\\Factories\\', $packageRoot . '/database/factories');
    $composerAutoloader->addPsr4('Capell\\SeoSuite\\Tests\\', $packageRoot . '/tests');
}

beforeEach(function (): void {
    Cache::flush();
    config()->set('capell-seo-suite.ai_discovery.default_crawler_rules');
    Date::setTestNow();
});

afterEach(function (): void {
    Date::setTestNow();
});

function createAiDiscoveryLanguage(): Language
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

it('groups llms txt entries by section and orders by priority', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $firstPage = Page::factory()
        ->site($site)
        ->withTranslations($language, [
            'title' => 'First Page',
            'meta' => ['description' => 'SEO fallback'],
        ])
        ->create();
    $secondPage = Page::factory()
        ->site($site)
        ->withTranslations($language, ['title' => 'Second Page'])
        ->create();

    ResolveAiDiscoveryProfileAction::run($site, $language, $firstPage);
    ResolveAiDiscoveryProfileAction::run($site, $language, $secondPage);
    AiDiscoveryPageProfile::query()->where('page_id', $firstPage->getKey())->update([
        'summary' => 'First AI summary',
        'section' => 'Guides',
        'priority' => 200,
    ]);
    AiDiscoveryPageProfile::query()->where('page_id', $secondPage->getKey())->update([
        'summary' => 'Second AI summary',
        'section' => 'Guides',
        'priority' => 100,
    ]);

    $content = GenerateLlmsTxtAction::run($site, $language);

    expect($content)->toStartWith('# ')
        ->and($content)->toContain('## Guides')
        ->and($content)->toContain('Second AI summary')
        ->and($content)->toContain('First AI summary')
        ->and($content)->not->toContain('SEO fallback')
        ->and(strpos($content, 'Second Page'))->toBeLessThan(strpos($content, 'First Page'));
});

it('falls back to canonical page url when markdown pages are disabled', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    Page::factory()->site($site)->withTranslations($language, ['title' => 'About'])->create();
    $profile = ResolveAiDiscoveryProfileAction::run($site, $language);
    $profile->update(['markdown_pages_enabled' => false]);

    $content = GenerateLlmsTxtAction::run($site, $language);

    expect($content)->toContain('](http')
        ->and($content)->not->toContain('.md)');
});

it('uses markdown page urls when markdown pages are enabled', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    Page::factory()->site($site)->withTranslations($language, ['title' => 'About'])->create();
    ResolveAiDiscoveryProfileAction::run($site, $language);

    $content = GenerateLlmsTxtAction::run($site, $language);

    expect($content)->toContain('.md)');
});

it('returns empty content when llms txt is disabled', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    Page::factory()->site($site)->withTranslations($language, ['title' => 'About'])->create();
    $profile = ResolveAiDiscoveryProfileAction::run($site, $language);
    $profile->update(['llms_txt_enabled' => false]);

    $content = GenerateLlmsTxtAction::run($site, $language);

    expect($content)->toBe('');
});

it('returns empty content when ai discovery profile status is disabled', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    Page::factory()->site($site)->withTranslations($language, ['title' => 'About'])->create();
    $profile = ResolveAiDiscoveryProfileAction::run($site, $language);
    $profile->update(['status' => AiDiscoveryStatusEnum::Disabled->value]);

    $content = GenerateLlmsTxtAction::run($site, $language);

    expect($content)->toBe('');
});

it('uses index markdown url for the homepage instead of appending md to the host', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations(
        $language,
        siteDomainData: ['scheme' => 'https', 'domain' => 'example.test', 'path' => null],
    )->create();
    Page::factory()->home()->site($site)->withTranslations($language, ['title' => 'Home'], slug: '/')->create();
    ResolveAiDiscoveryProfileAction::run($site, $language);

    $content = GenerateLlmsTxtAction::run($site, $language);

    expect($content)->toContain('https://example.test/index.md')
        ->and($content)->not->toContain('https://example.test.md');
});

it('generates page markdown from profile and page content', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()->site($site)->withTranslations($language, [
        'title' => 'Service Page',
        'content' => '<p>Useful server-rendered content.</p>',
    ])->create();
    $profile = ResolveAiDiscoveryProfileAction::run($site, $language, $page);
    $profile->update(['summary' => 'Short AI summary.']);

    $content = GeneratePageMarkdownAction::run(new AiDiscoveryRenderContextData($site, $language), $page);

    expect($content)->toStartWith("# Service Page\n")
        ->and($content)->toContain('Short AI summary.')
        ->and($content)->toContain('Useful server-rendered content.')
        ->and($content)->not->toContain('<p>');
});

it('preserves common content structure when generating page markdown', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()->site($site)->withTranslations($language, [
        'title' => 'Structured Page',
        'content' => '<h2>Features</h2><p>Read the <a href="/guide">guide</a>.</p><ul><li>Fast setup</li><li>Clean output</li></ul>',
    ])->create();

    $content = GeneratePageMarkdownAction::run(new AiDiscoveryRenderContextData($site, $language), $page);

    expect($content)->toContain('## Features')
        ->and($content)->toContain('[guide](/guide)')
        ->and($content)->toContain('- Fast setup')
        ->and($content)->toContain('- Clean output')
        ->and($content)->not->toContain('<h2>');
});

it('generates capped llms full txt with page markdown bodies', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    Page::factory()->site($site)->withTranslations($language, [
        'title' => 'First Page',
        'content' => '<p>First markdown body.</p>',
    ])->create();
    Page::factory()->site($site)->withTranslations($language, [
        'title' => 'Second Page',
        'content' => '<p>Second markdown body.</p>',
    ])->create();
    $profile = ResolveAiDiscoveryProfileAction::run($site, $language);
    $profile->update([
        'llms_full_txt_enabled' => true,
        'max_full_txt_pages' => 1,
        'max_full_txt_bytes' => 10000,
    ]);

    $content = GenerateLlmsFullTxtAction::run($site, $language);

    expect($content)->toContain('# ')
        ->and(substr_count($content, "\n---\n"))->toBe(1);
});

it('applies llms full txt page caps after ai index exclusions', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    Page::factory()->site($site)->withTranslations($language, [
        'title' => 'Excluded Page',
        'content' => '<p>Excluded markdown body.</p>',
    ])->create([
        'meta' => [
            'ai_discovery' => [
                'include_in_ai_index' => false,
            ],
        ],
    ]);
    Page::factory()->site($site)->withTranslations($language, [
        'title' => 'Included Page',
        'content' => '<p>Included markdown body.</p>',
    ])->create();
    $profile = ResolveAiDiscoveryProfileAction::run($site, $language);
    $profile->update([
        'llms_full_txt_enabled' => true,
        'max_full_txt_pages' => 1,
        'max_full_txt_bytes' => 10000,
    ]);

    $content = GenerateLlmsFullTxtAction::run($site, $language);

    expect($content)->toContain('Included markdown body.')
        ->and($content)->not->toContain('Excluded markdown body.')
        ->and(substr_count($content, "\n---\n"))->toBe(1);
});

it('serves llms full txt and page markdown through cache aware controllers', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $siteDomain = $site->siteDomains()->first();
    $page = Page::factory()->site($site)->withTranslations($language, [
        'title' => 'About',
        'content' => '<p>About markdown body.</p>',
    ])->create();
    $profile = ResolveAiDiscoveryProfileAction::run($site, $language);
    $profile->update(['llms_full_txt_enabled' => true]);

    resolve(FrontendState::class)
        ->withSite($site)
        ->withLanguage($language)
        ->withDomain($siteDomain)
        ->withPage($page);

    $fullResponse = resolve(LlmsFullTxtController::class)();
    $pageResponse = resolve(PageMarkdownController::class)(Request::create('/about.md'), 'about');

    expect($fullResponse->getStatusCode())->toBe(200)
        ->and($fullResponse->getContent())->toContain('About markdown body.')
        ->and($pageResponse->getStatusCode())->toBe(200)
        ->and($pageResponse->getContent())->toContain('# About')
        ->and(AiDiscoverySnapshot::query()->where('kind', AiDiscoverySnapshotKindEnum::LlmsFullTxt->value)->exists())->toBeTrue()
        ->and(AiDiscoverySnapshot::query()->where('kind', AiDiscoverySnapshotKindEnum::PageMarkdown->value)->exists())->toBeTrue();
});

it('does not serve direct page markdown for noindex pages', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $siteDomain = $site->siteDomains()->first();
    $page = Page::factory()
        ->site($site)
        ->withTranslations($language, [
            'title' => 'Private Page',
            'content' => '<p>Private markdown body.</p>',
        ])
        ->meta('robots', ['noindex'])
        ->create();

    ResolveAiDiscoveryProfileAction::run($site, $language);

    resolve(FrontendState::class)
        ->withSite($site)
        ->withLanguage($language)
        ->withDomain($siteDomain)
        ->withPage($page);

    expect(fn () => resolve(PageMarkdownController::class)(Request::create('/private-page.md'), 'private-page'))
        ->toThrow(NotFoundHttpException::class);
});

it('does not serve accept markdown unless the site language profile allows it', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $siteDomain = $site->siteDomains()->first();
    $page = Page::factory()->site($site)->withTranslations($language, [
        'title' => 'Accept Disabled Page',
        'content' => '<p>Accept disabled body.</p>',
    ])->create();
    ResolveAiDiscoveryProfileAction::run($site, $language);

    resolve(FrontendState::class)
        ->withSite($site)
        ->withLanguage($language)
        ->withDomain($siteDomain)
        ->withPage($page);

    $request = Request::create('/accept-disabled-page', Symfony\Component\HttpFoundation\Request::METHOD_GET, server: ['HTTP_ACCEPT' => 'text/markdown']);

    $response = resolve(PageMarkdownController::class)->forAcceptHeader($request);

    expect($response)->toBeNull();
});

it('serves current frontend pages as markdown for text markdown accept requests', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $siteDomain = $site->siteDomains()->first();
    $page = Page::factory()->site($site)->withTranslations($language, [
        'title' => 'Accept Page',
        'content' => '<p>Accept markdown body.</p>',
    ])->create();
    $profile = ResolveAiDiscoveryProfileAction::run($site, $language);
    $profile->update(['accept_markdown_enabled' => true]);

    resolve(FrontendState::class)
        ->withSite($site)
        ->withLanguage($language)
        ->withDomain($siteDomain)
        ->withPage($page);

    $request = Request::create('/accept-page', Symfony\Component\HttpFoundation\Request::METHOD_GET, server: ['HTTP_ACCEPT' => 'text/markdown']);
    app()->instance('request', $request);
    app()->instance(Request::class, $request);

    $response = resolve(FrontendPageController::class)();

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Content-Type'))->toBe('text/markdown; charset=utf-8')
        ->and($response->getContent())->toContain('Accept markdown body.');
});

it('honors page ai discovery meta when rendering page markdown directly', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()->site($site)->withTranslations($language, [
        'title' => 'Excluded Direct Page',
        'content' => '<p>This should not render.</p>',
    ])->create([
        'meta' => [
            'ai_discovery' => [
                'include_in_ai_index' => false,
                'summary' => 'Editor-only summary',
            ],
        ],
    ]);

    $content = GeneratePageMarkdownAction::run(new AiDiscoveryRenderContextData($site, $language), $page);
    $profile = AiDiscoveryPageProfile::query()->where('page_id', $page->getKey())->sole();

    expect($content)->toBe('')
        ->and($profile->include_in_ai_index)->toBeFalse()
        ->and($profile->summary)->toBe('Editor-only summary');
});

it('clears cached ai discovery snapshots for page changes', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()->site($site)->withTranslations($language, ['title' => 'About'])->create();
    $context = new AiDiscoveryRenderContextData($site, $language);
    Cache::put('ai-discovery-test-key', '# Cached', 3600);
    PersistAiDiscoverySnapshotAction::run(
        context: $context,
        kind: AiDiscoverySnapshotKindEnum::PageMarkdown,
        content: '# Cached',
        cacheKey: 'ai-discovery-test-key',
        ttlSeconds: 3600,
        page: $page,
    );

    $cleared = ClearAiDiscoveryCacheAction::run($site, page: $page);

    expect($cleared)->toBe(1)
        ->and(Cache::has('ai-discovery-test-key'))->toBeFalse()
        ->and(AiDiscoverySnapshot::query()->sole()->status)->toBe(AiDiscoveryStatusEnum::Stale);
});

it('clears cached ai discovery snapshots when site profile settings change', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $context = new AiDiscoveryRenderContextData($site, $language);
    Cache::put('ai-discovery-site-profile-key', '# Cached', 3600);
    PersistAiDiscoverySnapshotAction::run(
        context: $context,
        kind: AiDiscoverySnapshotKindEnum::LlmsTxt,
        content: '# Cached',
        cacheKey: 'ai-discovery-site-profile-key',
        ttlSeconds: 3600,
    );
    $profile = ResolveAiDiscoveryProfileAction::run($site, $language);

    $profile->update(['intro_markdown' => 'Updated intro']);

    expect(Cache::has('ai-discovery-site-profile-key'))->toBeFalse()
        ->and(AiDiscoverySnapshot::query()->sole()->status)->toBe(AiDiscoveryStatusEnum::Stale);
});

it('seeds and renders default ai crawler robots rules', function (): void {
    $created = SeedDefaultAiCrawlerRulesAction::run();
    $rules = BuildAiRobotsTxtRulesAction::run();

    expect($created)->toBeGreaterThan(0)
        ->and(AiDiscoveryCrawlerRule::query()->count())->toBe($created)
        ->and($rules)->toContain('# Capell AI Discovery managed rules')
        ->and($rules)->toContain("User-agent: OAI-SearchBot\nAllow: /")
        ->and($rules)->toContain("User-agent: GPTBot\nDisallow: /")
        ->and($rules)->toContain("User-agent: ClaudeBot\nDisallow: /")
        ->and($rules)->toContain("User-agent: ChatGPT-User\nAllow: /")
        ->and($rules)->toContain("User-agent: Claude-User\nAllow: /")
        ->and($rules)->toContain("User-agent: Google-Extended\nDisallow: /")
        ->and($rules)->toContain("User-agent: CCBot\nDisallow: /")
        ->and(AiDiscoveryCrawlerRule::query()->where('user_agent', 'OAI-SearchBot')->value('source_url'))->toBe('https://platform.openai.com/docs/bots');
});

it('seeds ai crawler rules from package configuration', function (): void {
    config()->set('capell-seo-suite.ai_discovery.default_crawler_rules', [
        [
            'provider' => 'Example',
            'user_agent' => 'ExampleBot',
            'purpose' => 'search',
            'directive' => 'allow',
            'path' => '/public',
            'source_url' => 'https://example.test/bot',
            'notes' => 'Configurable example bot.',
        ],
    ]);

    $created = SeedDefaultAiCrawlerRulesAction::run();
    $rules = BuildAiRobotsTxtRulesAction::run();

    expect($created)->toBe(1)
        ->and($rules)->toContain("User-agent: ExampleBot\nAllow: /public");
});

it('serves ai crawler rules through robots txt and allows site rules to override defaults', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    SeedDefaultAiCrawlerRulesAction::run();
    $siteSpecificRule = AiDiscoveryCrawlerRule::query()
        ->whereNull('site_id')
        ->where('user_agent', 'GPTBot')
        ->firstOrFail()
        ->replicate();
    $siteSpecificRule->site_id = $site->getKey();
    $siteSpecificRule->enabled = false;
    $siteSpecificRule->save();

    resolve(FrontendState::class)
        ->withSite($site)
        ->withLanguage($language)
        ->withDomain($site->siteDomains()->first());

    $response = resolve(RobotsTxtController::class)();

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Content-Type'))->toBe('text/plain; charset=utf-8')
        ->and($response->getContent())->not->toContain("User-agent: GPTBot\nDisallow: /")
        ->and($response->getContent())->toContain("User-agent: OAI-SearchBot\nAllow: /");
});

it('syncs site language ai discovery settings from site translation meta', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language, [
        'meta' => [
            'ai_discovery' => [
                'llms_txt_enabled' => false,
                'llms_full_txt_enabled' => true,
                'markdown_pages_enabled' => false,
                'accept_markdown_enabled' => true,
                'default_include_pages' => false,
                'max_full_txt_pages' => 7,
                'max_full_txt_bytes' => 12345,
                'cache_ttl_seconds' => 99,
                'default_section' => 'Docs',
                'intro_markdown' => 'Intro from settings.',
                'status' => AiDiscoveryStatusEnum::Disabled->value,
            ],
        ],
    ])->create();

    $profile = ResolveAiDiscoveryProfileAction::run($site, $language);

    expect($profile->llms_txt_enabled)->toBeFalse()
        ->and($profile->llms_full_txt_enabled)->toBeTrue()
        ->and($profile->markdown_pages_enabled)->toBeFalse()
        ->and($profile->accept_markdown_enabled)->toBeTrue()
        ->and($profile->default_include_pages)->toBeFalse()
        ->and($profile->max_full_txt_pages)->toBe(7)
        ->and($profile->max_full_txt_bytes)->toBe(12345)
        ->and($profile->cache_ttl_seconds)->toBe(99)
        ->and($profile->default_section)->toBe('Docs')
        ->and($profile->intro_markdown)->toBe('Intro from settings.')
        ->and($profile->status)->toBe(AiDiscoveryStatusEnum::Disabled);
});

it('syncs page profile quick-fill values from page meta', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()->site($site)->withTranslations($language, ['title' => 'About'])->create([
        'meta' => [
            'ai_discovery' => [
                'include_in_ai_index' => false,
                'summary' => 'Editor summary',
                'section' => 'Products',
                'priority' => 123,
                'exclude_reason' => 'Private',
            ],
        ],
    ]);

    $profiles = SyncAiDiscoveryPageProfilesAction::run($site, $language);
    $profile = $profiles->firstWhere('page_id', $page->getKey());

    expect($profile)->not->toBeNull()
        ->and($profile->include_in_ai_index)->toBeFalse()
        ->and($profile->summary)->toBe('Editor summary')
        ->and($profile->section)->toBe('Products')
        ->and($profile->priority)->toBe(123)
        ->and($profile->exclude_reason)->toBe('Private');
});

it('reports ai readiness gaps for weak public content', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()->site($site)->withTranslations($language, [
        'title' => 'Short',
        'content' => '',
        'meta' => [],
    ])->create();

    $issues = BuildAiReadinessAuditAction::run($page, $site, $language);

    expect($issues->pluck('key')->all())->toContain(
        'missing_summary',
        'weak_title',
        'missing_schema',
        'js_only_content',
    );
});

it('persists ai discovery snapshots by context key', function (): void {
    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $siteDomain = $site->siteDomains()->first();
    $context = new AiDiscoveryRenderContextData($site, $language, $siteDomain);

    $snapshot = PersistAiDiscoverySnapshotAction::run(
        context: $context,
        kind: AiDiscoverySnapshotKindEnum::LlmsTxt,
        content: '# Site',
        cacheKey: 'capell-seo-suite:ai-discovery:1:default:1:llms_txt',
        ttlSeconds: 3600,
    );

    $updatedSnapshot = PersistAiDiscoverySnapshotAction::run(
        context: $context,
        kind: AiDiscoverySnapshotKindEnum::LlmsTxt,
        content: '# Updated Site',
        cacheKey: 'capell-seo-suite:ai-discovery:1:default:1:llms_txt',
        ttlSeconds: 1800,
        status: AiDiscoveryStatusEnum::Stale->value,
    );

    expect($updatedSnapshot->is($snapshot))->toBeTrue()
        ->and(AiDiscoverySnapshot::query()->count())->toBe(1)
        ->and($updatedSnapshot->context_key)->toBe($context->domainKey() . ':site')
        ->and($updatedSnapshot->site_domain_id)->toBe($siteDomain?->getKey())
        ->and($updatedSnapshot->content_hash)->toBe(hash('sha256', '# Updated Site'))
        ->and($updatedSnapshot->byte_size)->toBe(strlen('# Updated Site'))
        ->and($updatedSnapshot->status)->toBe(AiDiscoveryStatusEnum::Stale)
        ->and($updatedSnapshot->expires_at)->not->toBeNull();
});

it('serves llms txt with markdown headers and only persists snapshots on cache misses', function (): void {
    Date::setTestNow(Date::parse('2026-05-09 12:00:00'));

    $language = createAiDiscoveryLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $siteDomain = $site->siteDomains()->first();
    Page::factory()->site($site)->withTranslations($language, ['title' => 'About'])->create();
    $profile = ResolveAiDiscoveryProfileAction::run($site, $language);
    $profile->update(['cache_ttl_seconds' => 3600]);

    resolve(FrontendState::class)
        ->withSite($site)
        ->withLanguage($language)
        ->withDomain($siteDomain);

    $firstResponse = resolve(LlmsTxtController::class)();
    $snapshot = AiDiscoverySnapshot::query()->sole();
    $generatedAt = $snapshot->generated_at?->toImmutable();
    $cacheKey = sprintf(
        'capell-seo-suite:ai-discovery:%d:%s:%d:llms_txt',
        $site->getKey(),
        (new AiDiscoveryRenderContextData($site, $language, $siteDomain))->domainKey(),
        $language->getKey(),
    );

    Date::setTestNow(Date::parse('2026-05-09 12:10:00'));

    $secondResponse = resolve(LlmsTxtController::class)();
    $snapshot->refresh();

    expect($firstResponse->getStatusCode())->toBe(200)
        ->and($firstResponse->headers->get('Content-Type'))->toBe('text/markdown; charset=utf-8')
        ->and($firstResponse->headers->getCacheControlDirective('public'))->toBeTrue()
        ->and($firstResponse->headers->getCacheControlDirective('max-age'))->toBe('3600')
        ->and($firstResponse->headers->get('ETag'))->toBe('"' . hash('sha256', $firstResponse->getContent()) . '"')
        ->and(Cache::has($cacheKey))->toBeTrue()
        ->and($secondResponse->getContent())->toBe($firstResponse->getContent())
        ->and(AiDiscoverySnapshot::query()->count())->toBe(1)
        ->and($snapshot->cache_key)->toBe($cacheKey)
        ->and($snapshot->generated_at?->equalTo($generatedAt))->toBeTrue();
});
