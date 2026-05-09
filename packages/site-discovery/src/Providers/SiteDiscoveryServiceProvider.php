<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Providers;

use Capell\Admin\Contracts\AdminTools\AdminToolItem;
use Capell\Admin\Contracts\Extenders\ResourceHeaderActionExtender;
use Capell\Admin\Contracts\Extenders\SiteHeaderActionExtender;
use Capell\Admin\Contracts\Extenders\SiteRecordActionExtender;
use Capell\Admin\Support\CapellAdminManager;
use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Data\PackageData;
use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Events\PageDeleted;
use Capell\Core\Events\PageSaved;
use Capell\Core\Events\SiteCreated;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\SiteDiscovery\Console\Commands\XmlSitemapCommand;
use Capell\SiteDiscovery\Filament\Extenders\Page\SitemapResourceHeaderActionExtender;
use Capell\SiteDiscovery\Filament\Extenders\Site\SitemapSiteHeaderActionExtender;
use Capell\SiteDiscovery\Filament\Extenders\Site\SitemapSiteRecordActionExtender;
use Capell\SiteDiscovery\Filament\Pages\SitemapPage;
use Capell\SiteDiscovery\Listeners\Sitemap\RegenerateSitemapsOnPageDeleted;
use Capell\SiteDiscovery\Listeners\Sitemap\RegenerateSitemapsOnPageSaved;
use Capell\SiteDiscovery\Listeners\Sitemap\RegenerateSitemapsOnSiteCreated;
use Capell\SiteDiscovery\Livewire\Page\Sitemap as SitemapLivewireComponent;
use Capell\SiteDiscovery\Livewire\Tools\SitemapTool;
use Capell\SiteDiscovery\Support\AdminTools\SitemapAdminTool;
use Capell\SiteDiscovery\Support\Creator\SitemapPageCreator;
use Capell\SiteDiscovery\Support\Interceptors\SitemapPageTypeInterceptor;
use Capell\SiteDiscovery\Support\Sitemap\Pages\PagesSitemap;
use Capell\SiteDiscovery\Support\Sitemap\SitemapPageRegistry;
use Capell\SiteDiscovery\Support\Sitemap\SitemapPageType;
use Composer\InstalledVersions;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;

final class SiteDiscoveryServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-site-discovery';

    public static string $packageName = 'capell-app/site-discovery';

    public static PackageTypeEnum $type = PackageTypeEnum::Plugin;

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasCommands([
                XmlSitemapCommand::class,
            ]);
    }

    public function registeringPackage(): void
    {
        $this->registerPackageMetadata();

        $this->app->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this->bootInstalledPackage();
        });
    }

    private function bootInstalledPackage(): self
    {
        return $this
            ->registerBlazeComponents()
            ->registerAdminExtenders()
            ->registerFilamentPages()
            ->registerLivewireComponents()
            ->registerSitemapPageType()
            ->registerSitemapDefaultPage()
            ->registerSitemapRegistry()
            ->registerSitemapEventListeners()
            ->registerFrontendViews();
    }

    private function registerBlazeComponents(): self
    {
        RegisterBlazeOptimizedViewsAction::run(__DIR__ . '/../../resources/views/components/pages');

        return $this;
    }

    private function registerAdminExtenders(): self
    {
        $this->app->tag([
            SitemapSiteHeaderActionExtender::class,
        ], SiteHeaderActionExtender::TAG);

        $this->app->tag([
            SitemapResourceHeaderActionExtender::class,
        ], ResourceHeaderActionExtender::TAG);

        $this->app->tag([
            SitemapSiteRecordActionExtender::class,
        ], SiteRecordActionExtender::TAG);

        $this->app->tag([
            SitemapAdminTool::class,
        ], AdminToolItem::TAG);

        return $this;
    }

    private function registerFilamentPages(): self
    {
        /** @var CapellAdminManager $adminManager */
        $adminManager = $this->app->make(CapellAdminManager::class);

        $adminManager->registerExtensionPage(self::$packageName, SitemapPage::class);

        return $this;
    }

    private function registerLivewireComponents(): self
    {
        Livewire::component(SitemapPageType::ComponentView, SitemapLivewireComponent::class);
        Livewire::component('capell-site-discovery.tools.sitemap-tool', SitemapTool::class);

        return $this;
    }

    private function registerSitemapPageType(): self
    {
        /** @var class-string<Type> $typeModel */
        $typeModel = Type::class;

        CapellCore::registerModelInterceptor(
            $typeModel,
            interceptorClass: SitemapPageTypeInterceptor::class,
            key: [
                'key' => SitemapPageType::Key,
                'type' => TypeEnum::Page,
            ],
        );

        return $this;
    }

    private function registerSitemapDefaultPage(): self
    {
        CapellCore::addDefaultPage(
            'sitemap',
            __('capell-site-discovery::generic.sitemap'),
            function (Site $site, ?Collection $languages = null): void {
                resolve(SitemapPageCreator::class)->createSitemapPage($site, $languages);
            },
        );

        return $this;
    }

    private function registerSitemapRegistry(): self
    {
        $this->app->singleton(SitemapPageRegistry::class);

        /** @var SitemapPageRegistry $registry */
        $registry = $this->app->make(SitemapPageRegistry::class);
        $registry->register('default', PagesSitemap::class);

        return $this;
    }

    private function registerSitemapEventListeners(): self
    {
        $events = $this->app->make(Dispatcher::class);
        $events->listen(PageSaved::class, RegenerateSitemapsOnPageSaved::class);
        $events->listen(PageDeleted::class, RegenerateSitemapsOnPageDeleted::class);
        $events->listen(SiteCreated::class, RegenerateSitemapsOnSiteCreated::class);

        return $this;
    }

    private function registerFrontendViews(): self
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell');

        return $this;
    }

    private function isPackageInstalled(): bool
    {
        $package = CapellCore::getPackage(self::$packageName);

        return $package instanceof PackageData && $package->isInstalled();
    }

    private function registerPackageMetadata(): void
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            permissions: [],
            description: fn (): string => __('capell-site-discovery::package.description'),
        );
    }

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'dev';
        }

        if (! InstalledVersions::isInstalled(self::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(self::$packageName) ?? 'dev';
    }
}
