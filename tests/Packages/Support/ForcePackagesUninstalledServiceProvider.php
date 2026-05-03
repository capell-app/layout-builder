<?php

declare(strict_types=1);

namespace Capell\Tests\Packages\Support;

use Capell\Address\Providers\AddressServiceProvider;
use Capell\Analytics\Providers\AnalyticsServiceProvider;
use Capell\Assistant\Providers\AssistantServiceProvider;
use Capell\AuthenticationLog\Providers\AuthenticationLogServiceProvider;
use Capell\Backup\Providers\BackupServiceProvider;
use Capell\Blog\Providers\BlogServiceProvider;
use Capell\Campaigns\Providers\CampaignsServiceProvider;
use Capell\ContentBlocks\Providers\ContentBlocksServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\DeveloperTools\Providers\DeveloperToolsServiceProvider;
use Capell\FilamentPeek\Providers\FilamentPeekServiceProvider;
use Capell\Forms\Providers\FormsServiceProvider;
use Capell\HtmlMinify\Providers\HtmlMinifyServiceProvider;
use Capell\Mcp\Providers\CapellMcpServiceProvider;
use Capell\MediaCurator\CapellMediaCuratorServiceProvider;
use Capell\Mosaic\Providers\MosaicServiceProvider;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\Redirects\Providers\RedirectsServiceProvider;
use Capell\SeoTools\Providers\SeoToolsServiceProvider;
use Capell\SiteSearch\Providers\SiteSearchServiceProvider;
use Capell\Tags\Providers\TagsServiceProvider;
use Capell\ThemeStudio\Admin\ThemeStudioAdminServiceProvider;
use Capell\ThemeStudio\Core\ThemeStudioCoreServiceProvider;
use Capell\Toolbar\Providers\ToolbarServiceProvider;
use Capell\Workspaces\Providers\WorkspacesServiceProvider;
use Illuminate\Support\ServiceProvider;

class ForcePackagesUninstalledServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ($this->packageNames() as $packageName) {
            CapellCore::forcePackageInstalled($packageName, false);
        }
    }

    /** @return list<string> */
    private function packageNames(): array
    {
        return [
            AddressServiceProvider::$packageName,
            AnalyticsServiceProvider::$packageName,
            AssistantServiceProvider::$packageName,
            AuthenticationLogServiceProvider::$packageName,
            BackupServiceProvider::$packageName,
            BlogServiceProvider::$packageName,
            CampaignsServiceProvider::$packageName,
            ContentBlocksServiceProvider::$packageName,
            DeveloperToolsServiceProvider::$packageName,
            FilamentPeekServiceProvider::$packageName,
            FormsServiceProvider::$packageName,
            HtmlMinifyServiceProvider::$packageName,
            CapellMcpServiceProvider::$packageName,
            CapellMediaCuratorServiceProvider::$packageName,
            MosaicServiceProvider::$packageName,
            NavigationServiceProvider::$packageName,
            RedirectsServiceProvider::$packageName,
            SeoToolsServiceProvider::$packageName,
            SiteSearchServiceProvider::$packageName,
            TagsServiceProvider::$packageName,
            ThemeStudioAdminServiceProvider::$packageName,
            ThemeStudioCoreServiceProvider::$packageName,
            ToolbarServiceProvider::$packageName,
            WorkspacesServiceProvider::$packageName,
            'capell-app/theme-agency',
            'capell-app/theme-corporate',
            'capell-app/theme-saas',
        ];
    }
}
