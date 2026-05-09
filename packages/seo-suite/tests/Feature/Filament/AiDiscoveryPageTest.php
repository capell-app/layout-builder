<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Actions\BuildAiRobotsTxtRulesAction;
use Capell\SeoSuite\Actions\DashboardReports\BuildAiDiscoveryPageQueryAction;
use Capell\SeoSuite\Actions\FillAiDiscoveryPageSummaryAction;
use Capell\SeoSuite\Actions\SeedDefaultAiCrawlerRulesAction;
use Capell\SeoSuite\Actions\UpdateAiDiscoveryPageInclusionAction;
use Capell\SeoSuite\Filament\Pages\AiDiscoveryPage;
use Capell\SeoSuite\Models\AiDiscoveryCrawlerRule;
use Capell\SeoSuite\Models\AiDiscoveryPageProfile;
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
