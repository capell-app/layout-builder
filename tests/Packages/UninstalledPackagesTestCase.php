<?php

declare(strict_types=1);

namespace Capell\Tests\Packages;

use Capell\Address\Providers\AddressServiceProvider;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\AdminPreview\Providers\AdminPreviewServiceProvider;
use Capell\AgentBridge\Providers\AgentBridgeServiceProvider;
use Capell\AIOrchestrator\Providers\AIOrchestratorServiceProvider;
use Capell\Blog\Providers\BlogServiceProvider;
use Capell\Blog\Providers\FrontendServiceProvider as BlogFrontendServiceProvider;
use Capell\CampaignStudio\Providers\CampaignStudioServiceProvider;
use Capell\Core\Providers\CapellServiceProvider;
use Capell\Diagnostics\Providers\DiagnosticsServiceProvider;
use Capell\FormBuilder\Providers\FormBuilderServiceProvider as CapellFormBuilderServiceProvider;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\FrontendAuthoring\Providers\FrontendAuthoringServiceProvider;
use Capell\HtmlOptimizer\Providers\HtmlOptimizerServiceProvider;
use Capell\Insights\Providers\InsightsServiceProvider;
use Capell\LayoutBuilder\Providers\LayoutBuilderServiceProvider;
use Capell\LoginAudit\Providers\LoginAuditServiceProvider;
use Capell\MediaLibrary\MediaLibraryServiceProvider;
use Capell\MigrationAssistant\Providers\MigrationAssistantServiceProvider;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\PublishingStudio\Providers\PublishingStudioServiceProvider;
use Capell\Redirects\Providers\RedirectsServiceProvider;
use Capell\Search\Providers\SearchServiceProvider;
use Capell\SeoSuite\Providers\SeoSuiteServiceProvider;
use Capell\Tags\Providers\TagsServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Packages\Support\ForcePackagesUninstalledServiceProvider;
use Capell\ThemeStudio\Agency\AgencyThemeServiceProvider;
use Capell\ThemeStudio\Corporate\CorporateThemeServiceProvider;
use Capell\ThemeStudio\Saas\SaasThemeServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;

class UninstalledPackagesTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-uninstalled-packages';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            ForcePackagesUninstalledServiceProvider::class,
            AddressServiceProvider::class,
            InsightsServiceProvider::class,
            AIOrchestratorServiceProvider::class,
            LoginAuditServiceProvider::class,
            MigrationAssistantServiceProvider::class,
            LayoutBuilderServiceProvider::class,
            NavigationServiceProvider::class,
            BlogServiceProvider::class,
            BlogFrontendServiceProvider::class,
            CampaignStudioServiceProvider::class,
            CapellFormBuilderServiceProvider::class,
            DiagnosticsServiceProvider::class,
            SeoSuiteServiceProvider::class,
            SearchServiceProvider::class,
            TagsServiceProvider::class,
            FrontendAuthoringServiceProvider::class,
            AdminPreviewServiceProvider::class,
            PublishingStudioServiceProvider::class,
            RedirectsServiceProvider::class,
            MediaLibraryServiceProvider::class,
            HtmlOptimizerServiceProvider::class,
            AgentBridgeServiceProvider::class,
            AgencyThemeServiceProvider::class,
            CorporateThemeServiceProvider::class,
            SaasThemeServiceProvider::class,
            FrontendServiceProvider::class,
            CapellServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
            FoundationThemeServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }
}
