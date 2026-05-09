<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\SeoSuite\Actions\BuildAiReadinessAuditAction;
use Capell\SeoSuite\Actions\BuildAiRobotsTxtRulesAction;
use Capell\SeoSuite\Actions\DashboardReports\BuildAiDiscoveryPageQueryAction;
use Capell\SeoSuite\Actions\FillAiDiscoveryPageSummaryAction;
use Capell\SeoSuite\Actions\ResolveAiDiscoveryProfileAction;
use Capell\SeoSuite\Actions\SeedDefaultAiCrawlerRulesAction;
use Capell\SeoSuite\Actions\UpdateAiDiscoveryPageInclusionAction;
use Capell\SeoSuite\Actions\UpdateAiDiscoveryPageProfileAction;
use Capell\SeoSuite\Filament\Pages\AiDiscoveryPage;
use Capell\SeoSuite\Filament\Pages\Tables\AiDiscoveryTable;
use Capell\SeoSuite\Models\AiDiscoveryCrawlerRule;
use Capell\SeoSuite\Models\AiDiscoveryPageProfile;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use Capell\SeoSuite\Settings\SeoSuiteSettings;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('registers ai discovery as an admin monitoring page', function (): void {
    expect(AiDiscoveryPage::getNavigationLabel())->toBe(__('capell-seo-suite::generic.ai_discovery'))
        ->and(AiDiscoveryPage::getNavigationGroup())->toBe(__('capell-admin::navigation.group_monitoring'));
});

it('builds the ai discovery page query through the current site scope', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()->site($site)->withTranslations($language, ['title' => 'AI Discovery Page'])->create();

    expect(BuildAiDiscoveryPageQueryAction::run()->whereKey($page)->exists())->toBeTrue();
});

it('fills ai discovery summaries from existing seo metadata', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()
        ->site($site)
        ->withTranslations($language, [
            'title' => 'AI Discovery Page',
            'meta' => ['description' => 'A concise public summary for AI discovery output.'],
        ])
        ->create();

    $profile = FillAiDiscoveryPageSummaryAction::run($page, $site, $language);

    expect($profile->summary)->toBe('A concise public summary for AI discovery output.')
        ->and(AiDiscoveryPageProfile::query()->where('page_id', $page->getKey())->exists())->toBeTrue();
});

it('updates ai discovery page inclusion without editing public page content', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()
        ->site($site)
        ->withTranslations($language, [
            'title' => 'AI Discovery Page',
            'content' => '<p>Published content stays intact.</p>',
        ])
        ->create();

    $excludedProfile = UpdateAiDiscoveryPageInclusionAction::run($page, $site, $language, false, 'Private launch page');
    $includedProfile = UpdateAiDiscoveryPageInclusionAction::run($page, $site, $language, true);

    $page->refresh();

    expect($excludedProfile->include_in_ai_index)->toBeFalse()
        ->and($excludedProfile->exclude_reason)->toBe('Private launch page')
        ->and($includedProfile->include_in_ai_index)->toBeTrue()
        ->and($includedProfile->exclude_reason)->toBeNull()
        ->and($page->translations()->first()?->content)->toBe('<p>Published content stays intact.</p>');
});

it('updates ai discovery page profile fields from the discovery management surface', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()
        ->site($site)
        ->withTranslations($language, ['title' => 'AI Discovery Page'])
        ->create();

    $profile = UpdateAiDiscoveryPageProfileAction::run($page, $site, $language, [
        'include_in_ai_index' => false,
        'section' => 'Docs',
        'priority' => 25,
        'summary' => 'A managed AI summary.',
        'markdown_override' => "# Custom\n\nMarkdown body.",
        'exclude_reason' => 'Internal documentation.',
    ]);

    expect($profile->include_in_ai_index)->toBeFalse()
        ->and($profile->section)->toBe('Docs')
        ->and($profile->priority)->toBe(25)
        ->and($profile->summary)->toBe('A managed AI summary.')
        ->and($profile->markdown_override)->toBe("# Custom\n\nMarkdown body.")
        ->and($profile->exclude_reason)->toBe('Internal documentation.');
});

it('preserves omitted ai discovery page profile fields on partial updates', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()
        ->site($site)
        ->withTranslations($language, ['title' => 'AI Discovery Page'])
        ->create();

    UpdateAiDiscoveryPageProfileAction::run($page, $site, $language, [
        'section' => 'Docs',
        'summary' => 'A managed AI summary.',
        'markdown_override' => "# Custom\n\nMarkdown body.",
    ]);

    $profile = UpdateAiDiscoveryPageProfileAction::run($page, $site, $language, [
        'priority' => 25,
    ]);

    expect($profile->priority)->toBe(25)
        ->and($profile->section)->toBe('Docs')
        ->and($profile->summary)->toBe('A managed AI summary.')
        ->and($profile->markdown_override)->toBe("# Custom\n\nMarkdown body.");
});

it('filters missing page profiles through the site ai discovery default', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()
        ->site($site)
        ->withTranslations($language, ['title' => 'AI Discovery Page'])
        ->create();

    AiDiscoverySiteProfile::query()->create([
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'llms_txt_enabled' => true,
        'llms_full_txt_enabled' => false,
        'markdown_pages_enabled' => true,
        'accept_markdown_enabled' => false,
        'default_include_pages' => false,
        'max_full_txt_pages' => 50,
        'max_full_txt_bytes' => 250000,
        'cache_ttl_seconds' => 3600,
        'default_section' => 'Pages',
        'status' => 'enabled',
    ]);

    $whereIncluded = new ReflectionMethod(AiDiscoveryTable::class, 'whereIncluded');

    $includedQuery = $whereIncluded->invoke(null, Page::query()->whereKey($page), true);
    $excludedQuery = $whereIncluded->invoke(null, Page::query()->whereKey($page), false);

    expect($includedQuery->exists())->toBeFalse()
        ->and($excludedQuery->exists())->toBeTrue();
});

it('hides markdown previews for pages excluded from ai discovery', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()
        ->site($site)
        ->withTranslations($language, ['title' => 'Private AI Discovery Page'])
        ->create();

    SiteDomain::factory()
        ->site($site)
        ->language($language)
        ->default()
        ->create(['domain' => 'example.test', 'scheme' => 'https']);
    PageUrl::factory()
        ->site($site)
        ->language($language)
        ->page($page)
        ->state(['url' => '/private-ai-page'])
        ->create();
    ResolveAiDiscoveryProfileAction::run($site, $language)->update(['markdown_pages_enabled' => true]);
    UpdateAiDiscoveryPageInclusionAction::run($page, $site, $language, false, 'Private content');

    $markdownUrlFor = new ReflectionMethod(AiDiscoveryTable::class, 'markdownUrlFor');

    expect($markdownUrlFor->invoke(null, $page->fresh()))->toBeNull();
});

it('honors seo suite ai discovery default and audit settings', function (): void {
    /** @var AbstractTestCase $this */
    $this->registerAndMigrateSettings(
        [
            'create_seo_suite_settings',
            '2026_05_09_000001_update_seo_suite_settings_add_ai_discovery',
        ],
        dirname(__DIR__, 3) . '/database/settings',
    );

    $settings = resolve(SeoSuiteSettings::class);
    $settings->ai_discovery_default_enabled = false;
    $settings->ai_discovery_audit_enabled = false;
    $settings->save();

    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()
        ->site($site)
        ->withTranslations($language, [
            'title' => 'Weak',
            'content' => '',
        ])
        ->create();

    $siteProfile = ResolveAiDiscoveryProfileAction::run($site, $language);
    $issues = BuildAiReadinessAuditAction::run($page, $site, $language);

    expect($siteProfile)->toBeInstanceOf(AiDiscoverySiteProfile::class)
        ->and($siteProfile->llms_txt_enabled)->toBeFalse()
        ->and($siteProfile->markdown_pages_enabled)->toBeFalse()
        ->and($issues)->toHaveCount(0);
});

it('applies configurable ai crawler policy presets when seeding robots rules', function (): void {
    config()->set('capell-seo-suite.ai_discovery.crawler_policy', 'open');

    SeedDefaultAiCrawlerRulesAction::run();
    $robots = BuildAiRobotsTxtRulesAction::run();

    expect($robots)->toContain('# Capell AI Discovery managed rules')
        ->and($robots)->toContain("User-agent: GPTBot\nAllow: /")
        ->and($robots)->toContain("User-agent: ClaudeBot\nAllow: /")
        ->and(AiDiscoveryCrawlerRule::query()->where('user_agent', 'OAI-SearchBot')->value('source_url'))->toBe('https://platform.openai.com/docs/bots')
        ->and(AiDiscoveryCrawlerRule::query()->where('user_agent', 'PerplexityBot')->value('source_url'))->toBe('https://docs.perplexity.ai/guides/bots')
        ->and(AiDiscoveryCrawlerRule::query()->where('user_agent', 'CCBot')->value('source_url'))->toBe('https://commoncrawl.org/ccbot');
});
