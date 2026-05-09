<?php

declare(strict_types=1);

namespace Capell\Tests\Packages;

use Capell\Address\Providers\AddressServiceProvider;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\ResourceEnum as AdminResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Blog\Enums\ResourceEnum as BlogResourceEnum;
use Capell\Blog\Providers\BlogServiceProvider;
use Capell\Blog\Providers\FrontendServiceProvider as BlogFrontendServiceProvider;
use Capell\CampaignStudio\Providers\CampaignStudioServiceProvider;
use Capell\ContentBlocks\Providers\ContentBlocksServiceProvider;
use Capell\ContentSections\Providers\ContentSectionsServiceProvider;
use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Media;
use Capell\Core\Providers\CapellServiceProvider;
use Capell\DemoKit\Providers\DemoKitServiceProvider;
use Capell\Diagnostics\Filament\Pages\DiagnosticsPage;
use Capell\Diagnostics\Filament\Pages\PermissionAuditPage;
use Capell\Diagnostics\Filament\Pages\QueueHealthPage;
use Capell\Diagnostics\Filament\Pages\SystemHealthPage;
use Capell\Diagnostics\Providers\AdminServiceProvider as DiagnosticsAdminServiceProvider;
use Capell\Diagnostics\Providers\DiagnosticsServiceProvider;
use Capell\FormBuilder\Providers\FormBuilderServiceProvider as CapellFormBuilderServiceProvider;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\FrontendAuthoring\Providers\FrontendAuthoringServiceProvider;
use Capell\Insights\Providers\InsightsServiceProvider;
use Capell\LoginAudit\Providers\LoginAuditServiceProvider;
use Capell\MediaLibrary\Filament\Pages\MediaHealthPage;
use Capell\MediaLibrary\MediaLibraryServiceProvider;
use Capell\MigrationAssistant\Filament\Resources\ImportSessions\ImportSessionResource;
use Capell\MigrationAssistant\Providers\MigrationAssistantServiceProvider;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\PublishingStudio\Providers\PublishingStudioServiceProvider;
use Capell\Search\Providers\SearchServiceProvider;
use Capell\SeoSuite\Filament\Pages\BrokenLinksPage;
use Capell\SeoSuite\Filament\Pages\NotFoundUrlsPage;
use Capell\SeoSuite\Filament\Pages\SeoAuditPage;
use Capell\SeoSuite\Filament\Pages\TranslationCoveragePage;
use Capell\SeoSuite\Providers\SeoSuiteServiceProvider;
use Capell\Tags\Models\Tag;
use Capell\Tags\Providers\TagsServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use Livewire\LivewireServiceProvider;
use Spatie\ImageOptimizer\Optimizers\Svgo;

class PackagesTestCase extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->forcePackagesInstalled();
        $this->registerBlogResourcesForBlaze();
        $this->registerMigrationAssistantResourcesForFilament();
        $this->registerSeoSuitePagesForFilament();
        $this->registerDiagnosticsPagesForFilament();
        $this->registerBlazeOptimizedViews();

        $this->registerAndMigrateSettings(
            CapellCore::getSettingMigrations(),
            __DIR__ . '/../../vendor/capell-app/core/database/settings',
        );

        $this->registerAndMigrateSettings(
            CapellAdmin::getSettingMigrations(),
            __DIR__ . '/../../vendor/capell-app/admin/database/settings',
        );

        $this->registerAndMigrateSettings(
            resolve(SettingsMigrationProviderInterface::class)->getSettingMigrations(),
            __DIR__ . '/../../vendor/capell-app/frontend/database/settings',
        );
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-packages';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AddressServiceProvider::class,
            InsightsServiceProvider::class,
            LoginAuditServiceProvider::class,
            MigrationAssistantServiceProvider::class,
            ContentSectionsServiceProvider::class,
            ContentBlocksServiceProvider::class,
            NavigationServiceProvider::class,
            BlogServiceProvider::class,
            BlogFrontendServiceProvider::class,
            CampaignStudioServiceProvider::class,
            CapellFormBuilderServiceProvider::class,
            DemoKitServiceProvider::class,
            DiagnosticsServiceProvider::class,
            DiagnosticsAdminServiceProvider::class,
            SeoSuiteServiceProvider::class,
            SearchServiceProvider::class,
            TagsServiceProvider::class,
            FrontendAuthoringServiceProvider::class,
            PublishingStudioServiceProvider::class,
            MediaLibraryServiceProvider::class,
            FrontendServiceProvider::class,
            CapellServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
            FoundationThemeServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        $this->forcePackagesInstalled();
        $this->registerBlogResourcesForBlaze();
        $this->registerMigrationAssistantResourcesForFilament();
        $this->registerSeoSuitePagesForFilament();
        $this->registerDiagnosticsPagesForFilament();

        CapellCore::registerPackage('capell-app/navigation', path: realpath(__DIR__ . '/../../packages/navigation'));
        CapellCore::forcePackageInstalled('capell-app/navigation');

        $app->make(Repository::class)->set('tags.tag_model', Tag::class);
        $app->make(Repository::class)->set('media-library.media_model', Media::class);
        $app->make(Repository::class)->set('media-library.image_optimizers', [
            Svgo::class => [],
        ]);

        Gate::before(
            fn (mixed $user, string $ability): ?bool => $user?->hasRole('super_admin') ? true : null,
        );
    }

    private function registerBlazeOptimizedViews(): void
    {
        foreach ([
            __DIR__ . '/../../packages/blog/resources/views/components',
            __DIR__ . '/../../packages/foundation-theme/resources/views/layout-builder/components',
            __DIR__ . '/../../packages/seo-suite/resources/views/components/schema',
            __DIR__ . '/../../packages/foundation-theme/resources/views/components',
        ] as $path) {
            RegisterBlazeOptimizedViewsAction::run($path);
        }
    }

    private function registerBlogResourcesForBlaze(): void
    {
        CapellAdmin::contributeToAdminSurface(
            AdminSurfaceContributionData::resource(
                BlogResourceEnum::Article->value,
                group: AdminResourceEnum::Page->name,
                name: strtolower(BlogResourceEnum::Article->name),
            ),
        );
    }

    private function registerMigrationAssistantResourcesForFilament(): void
    {
        CapellAdmin::contributeToAdminSurface(
            AdminSurfaceContributionData::resource(ImportSessionResource::class, group: 'ImportSession'),
        );
    }

    private function registerSeoSuitePagesForFilament(): void
    {
        foreach ([BrokenLinksPage::class, NotFoundUrlsPage::class, SeoAuditPage::class, TranslationCoveragePage::class] as $page) {
            CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page($page));
        }
    }

    private function registerDiagnosticsPagesForFilament(): void
    {
        foreach ([DiagnosticsPage::class, MediaHealthPage::class, PermissionAuditPage::class, QueueHealthPage::class, SystemHealthPage::class] as $page) {
            CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page($page));
        }
    }

    private function forcePackagesInstalled(): void
    {
        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(ContentSectionsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(SeoSuiteServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(TagsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(InsightsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(MigrationAssistantServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(BlogServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(ContentBlocksServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(CampaignStudioServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(CapellFormBuilderServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(DemoKitServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(DiagnosticsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(AddressServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(LoginAuditServiceProvider::$packageName);
        CapellCore::forcePackageInstalled('capell-app/media-library');
        CapellCore::forcePackageInstalled(SearchServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendAuthoringServiceProvider::$packageName);
        CapellCore::forcePackageInstalled('capell-app/publishing-studio');
    }
}
