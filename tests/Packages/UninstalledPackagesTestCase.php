<?php

declare(strict_types=1);

namespace Capell\Tests\Packages;

use Capell\Address\Providers\AddressServiceProvider;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Analytics\Providers\AnalyticsServiceProvider;
use Capell\Assistant\Providers\AssistantServiceProvider;
use Capell\AuthenticationLog\Providers\AuthenticationLogServiceProvider;
use Capell\Backup\Providers\BackupServiceProvider;
use Capell\Blog\Providers\BlogServiceProvider;
use Capell\Blog\Providers\FrontendServiceProvider as BlogFrontendServiceProvider;
use Capell\Campaigns\Providers\CampaignsServiceProvider;
use Capell\ContentBlocks\Providers\ContentBlocksServiceProvider;
use Capell\Core\Providers\CapellServiceProvider;
use Capell\DefaultTheme\Providers\DefaultThemeServiceProvider;
use Capell\DeveloperTools\Providers\DeveloperToolsServiceProvider;
use Capell\FilamentPeek\Providers\FilamentPeekServiceProvider;
use Capell\Forms\Providers\FormsServiceProvider as CapellFormsServiceProvider;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\HtmlMinify\Providers\HtmlMinifyServiceProvider;
use Capell\Mcp\Providers\CapellMcpServiceProvider;
use Capell\MediaCurator\CapellMediaCuratorServiceProvider;
use Capell\Mosaic\Providers\MosaicServiceProvider;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\Redirects\Providers\RedirectsServiceProvider;
use Capell\SeoTools\Providers\SeoToolsServiceProvider;
use Capell\SiteSearch\Providers\SiteSearchServiceProvider;
use Capell\Tags\Providers\TagsServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Packages\Support\ForcePackagesUninstalledServiceProvider;
use Capell\ThemeStudio\Admin\ThemeStudioAdminServiceProvider;
use Capell\ThemeStudio\Agency\AgencyThemeServiceProvider;
use Capell\ThemeStudio\Core\ThemeStudioCoreServiceProvider;
use Capell\ThemeStudio\Corporate\CorporateThemeServiceProvider;
use Capell\ThemeStudio\Saas\SaasThemeServiceProvider;
use Capell\Toolbar\Providers\ToolbarServiceProvider;
use Capell\Workspaces\Providers\WorkspacesServiceProvider;
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
            AnalyticsServiceProvider::class,
            AssistantServiceProvider::class,
            AuthenticationLogServiceProvider::class,
            BackupServiceProvider::class,
            MosaicServiceProvider::class,
            NavigationServiceProvider::class,
            BlogServiceProvider::class,
            BlogFrontendServiceProvider::class,
            CampaignsServiceProvider::class,
            CapellFormsServiceProvider::class,
            ContentBlocksServiceProvider::class,
            DeveloperToolsServiceProvider::class,
            SeoToolsServiceProvider::class,
            SiteSearchServiceProvider::class,
            TagsServiceProvider::class,
            ToolbarServiceProvider::class,
            FilamentPeekServiceProvider::class,
            WorkspacesServiceProvider::class,
            RedirectsServiceProvider::class,
            CapellMediaCuratorServiceProvider::class,
            HtmlMinifyServiceProvider::class,
            CapellMcpServiceProvider::class,
            ThemeStudioAdminServiceProvider::class,
            ThemeStudioCoreServiceProvider::class,
            AgencyThemeServiceProvider::class,
            CorporateThemeServiceProvider::class,
            SaasThemeServiceProvider::class,
            FrontendServiceProvider::class,
            CapellServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
            DefaultThemeServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }
}
