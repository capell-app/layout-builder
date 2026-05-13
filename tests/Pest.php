<?php

declare(strict_types=1);

use Capell\AccessGate\Tests\TestCase as AccessGateTestCase;
use Capell\Address\Tests\AddressTestCase;
use Capell\AgentBridge\Tests\TestCase as AgentBridgeTestCase;
use Capell\AIOrchestrator\Tests\AIOrchestratorTestCase;
use Capell\Api\Tests\ApiTestCase;
use Capell\Blog\Tests\BlogTestCase;
use Capell\CampaignStudio\Tests\CampaignStudioTestCase;
use Capell\ContentSections\Tests\ContentSectionsTestCase;
use Capell\DemoKit\Tests\DemoKitTestCase;
use Capell\Deployments\Tests\TestCase as DeploymentsTestCase;
use Capell\Diagnostics\Tests\DiagnosticsTestCase;
use Capell\EmailStudio\Tests\EmailStudioTestCase;
use Capell\Events\Tests\EventsTestCase;
use Capell\FormBuilder\Tests\FormBuilderTestCase;
use Capell\FrontendAuthoring\Tests\FrontendAuthoringTestCase;
use Capell\FrontendOptimizer\Tests\FrontendOptimizerTestCase;
use Capell\Insights\Tests\InsightsTestCase;
use Capell\MediaAI\Tests\MediaAITestCase;
use Capell\MediaLibrary\Tests\MediaLibraryTestCase;
use Capell\MigrationAssistant\Tests\MigrationAssistantTestCase;
use Capell\Navigation\Tests\NavigationTestCase;
use Capell\Newsletter\Tests\NewsletterTestCase;
use Capell\Notes\Tests\NotesTestCase;
use Capell\PasswordPolicy\Tests\PasswordPolicyTestCase;
use Capell\PublicActions\Tests\PublicActionsTestCase;
use Capell\PublishingStudio\Tests\PublishingStudioTestCase;
use Capell\Search\Tests\SearchTestCase;
use Capell\SeoSuite\Tests\SeoSuiteTestCase;
use Capell\Tags\Tests\TagsTestCase;
use Capell\Tests\Packages\PackagesTestCase;
use Capell\Tests\Packages\UninstalledPackagesTestCase;
use Capell\WelcomeTour\Tests\WelcomeTourTestCase;
use Capell\WordPressImporter\Tests\WordPressImporterTestCase;

/**
 * @param  class-string  $testCase
 */
function extendCapellPackageTests(string $testCase, string $group, string $package): void
{
    pest()->extend($testCase)->group($group)->in(sprintf('../packages/%s/tests', $package), sprintf('../Packages/%s/tests', $package));
}

extendCapellPackageTests(AddressTestCase::class, 'address', 'address');
extendCapellPackageTests(AccessGateTestCase::class, 'access-gate', 'access-gate');
extendCapellPackageTests(AgentBridgeTestCase::class, 'agent-bridge', 'agent-bridge');
extendCapellPackageTests(AIOrchestratorTestCase::class, 'ai-orchestrator', 'ai-orchestrator');
extendCapellPackageTests(ApiTestCase::class, 'api', 'api');
extendCapellPackageTests(BlogTestCase::class, 'blog', 'blog');
extendCapellPackageTests(CampaignStudioTestCase::class, 'campaign-studio', 'campaign-studio');
extendCapellPackageTests(ContentSectionsTestCase::class, 'content-sections', 'content-sections');
extendCapellPackageTests(DemoKitTestCase::class, 'demo-kit', 'demo-kit');
extendCapellPackageTests(DeploymentsTestCase::class, 'deployments', 'deployments');
extendCapellPackageTests(DiagnosticsTestCase::class, 'diagnostics', 'diagnostics');
extendCapellPackageTests(EmailStudioTestCase::class, 'email-studio', 'email-studio');
extendCapellPackageTests(EventsTestCase::class, 'events', 'events');
extendCapellPackageTests(FormBuilderTestCase::class, 'form-builder', 'form-builder');
extendCapellPackageTests(FrontendAuthoringTestCase::class, 'frontend-authoring', 'frontend-authoring');
extendCapellPackageTests(FrontendOptimizerTestCase::class, 'frontend-optimizer', 'frontend-optimizer');
extendCapellPackageTests(PackagesTestCase::class, 'hero', 'hero');
extendCapellPackageTests(InsightsTestCase::class, 'insights', 'insights');
extendCapellPackageTests(PackagesTestCase::class, 'login-audit', 'login-audit');
extendCapellPackageTests(MediaAITestCase::class, 'media-ai', 'media-ai');
extendCapellPackageTests(MediaLibraryTestCase::class, 'media-library', 'media-library');
extendCapellPackageTests(MigrationAssistantTestCase::class, 'migration-assistant', 'migration-assistant');
extendCapellPackageTests(NavigationTestCase::class, 'navigation', 'navigation');
extendCapellPackageTests(NewsletterTestCase::class, 'newsletter', 'newsletter');
extendCapellPackageTests(NotesTestCase::class, 'notes', 'notes');
pest()->extend(PackagesTestCase::class)->in('Packages');
extendCapellPackageTests(PackagesTestCase::class, 'foundation-theme', 'foundation-theme');
extendCapellPackageTests(PasswordPolicyTestCase::class, 'password-policy', 'password-policy');
extendCapellPackageTests(PublishingStudioTestCase::class, 'publishing-studio', 'publishing-studio');
extendCapellPackageTests(PublicActionsTestCase::class, 'public-actions', 'public-actions');
extendCapellPackageTests(SearchTestCase::class, 'search', 'search');
extendCapellPackageTests(SeoSuiteTestCase::class, 'seo-suite', 'seo-suite');
extendCapellPackageTests(TagsTestCase::class, 'tags', 'tags');
extendCapellPackageTests(PackagesTestCase::class, 'theme-agency', 'theme-agency');
extendCapellPackageTests(PackagesTestCase::class, 'theme-corporate', 'theme-corporate');
extendCapellPackageTests(PackagesTestCase::class, 'theme-saas', 'theme-saas');
pest()->extend(UninstalledPackagesTestCase::class)->in('UninstalledPackages');
extendCapellPackageTests(WelcomeTourTestCase::class, 'welcome-tour', 'welcome-tour');
extendCapellPackageTests(WordPressImporterTestCase::class, 'wordpress-importer', 'wordpress-importer');
