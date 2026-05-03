<?php

declare(strict_types=1);

namespace Capell\Mcp\Providers;

use Capell\Admin\Contracts\AdminTools\AdminToolItem;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Mcp\Actions\Cache\ClearCapellCacheCapabilityAction;
use Capell\Mcp\Actions\Pages\CreateDraftPageCapabilityAction;
use Capell\Mcp\Actions\Pages\DisablePageCapabilityAction;
use Capell\Mcp\Actions\Pages\InspectPagePublishingReadinessCapabilityAction;
use Capell\Mcp\Actions\Pages\UpdateDraftPageCapabilityAction;
use Capell\Mcp\Contracts\CapellMcpCapabilityProvider;
use Capell\Mcp\Data\CapabilityData;
use Capell\Mcp\Enums\CapabilityRiskEnum;
use Capell\Mcp\Enums\CapabilityServerEnum;
use Capell\Mcp\Filament\Pages\CapellMcpPromptBuilderPage;
use Capell\Mcp\Support\AdminTools\PromptBuilderAdminTool;
use Capell\Mcp\Support\CapellMcpCapabilityRegistry;
use Capell\Mcp\Tools\Boost\ListBoostCapabilitiesTool;
use Capell\Mcp\Tools\Boost\PreviewBoostCapabilityTool;
use Filament\Pages\Page;
use Illuminate\Support\ServiceProvider;
use Laravel\Boost\Mcp\Boost;

final class CapellMcpServiceProvider extends ServiceProvider
{
    public static string $packageName = 'capell-app/mcp';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/capell-mcp.php', 'capell-mcp');

        $this->registerPackageMetadata();
    }

    public function boot(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->app->singleton(CapellMcpCapabilityRegistry::class, fn (): CapellMcpCapabilityRegistry => new CapellMcpCapabilityRegistry);

        $this->publishes([
            __DIR__ . '/../../config/capell-mcp.php' => config_path('capell-mcp.php'),
        ], 'capell-mcp-config');

        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'capell-mcp');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell-mcp');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/mcp.php');

        $this->registerAdminIntegration();
        $this->registerBoostIntegration();
        $this->registerBuiltInCapabilities();
        $this->registerTaggedCapabilityProviders();
    }

    private function registerBoostIntegration(): void
    {
        if (! class_exists(Boost::class)) {
            return;
        }

        $includedTools = config('boost.mcp.tools.include', []);

        config([
            'boost.mcp.tools.include' => array_values(array_unique([
                ...$includedTools,
                ListBoostCapabilitiesTool::class,
                PreviewBoostCapabilityTool::class,
            ])),
        ]);
    }

    private function registerPackageMetadata(): void
    {
        if (! class_exists(CapellCore::class)) {
            return;
        }

        CapellCore::registerPackage(
            name: self::$packageName,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
        );
    }

    private function isPackageInstalled(): bool
    {
        if (! class_exists(CapellCore::class)) {
            return true;
        }

        return CapellCore::isPackageInstalled(self::$packageName);
    }

    private function registerAdminIntegration(): void
    {
        $adminFacade = CapellAdmin::class;
        $adminToolItem = AdminToolItem::class;
        $filamentPage = Page::class;
        $promptBuilderPage = CapellMcpPromptBuilderPage::class;
        $promptBuilderTool = PromptBuilderAdminTool::class;

        if (! class_exists($adminFacade) || ! interface_exists($adminToolItem) || ! class_exists($filamentPage)) {
            return;
        }

        if (! class_exists($promptBuilderPage) || ! class_exists($promptBuilderTool)) {
            return;
        }

        $adminFacade::contributeToAdminSurface(AdminSurfaceContributionData::page($promptBuilderPage));

        $this->app->tag([
            $promptBuilderTool,
        ], constant($adminToolItem . '::TAG'));
    }

    private function registerBuiltInCapabilities(): void
    {
        $registry = $this->app->make(CapellMcpCapabilityRegistry::class);

        $registry->register(new CapabilityData(
            key: 'capell.cache.clear',
            name: 'Clear Capell caches',
            description: 'Clear Capell admin, frontend, schema, component, and application caches where commands are available.',
            scope: 'capell.cache.run',
            server: CapabilityServerEnum::Site,
            risk: CapabilityRiskEnum::Medium,
            actionClass: ClearCapellCacheCapabilityAction::class,
            requiredPackage: 'capell-app/core',
            auditEvent: 'capell_mcp.cache.clear',
        ));

        $registry->register(new CapabilityData(
            key: 'capell.pages.create_draft',
            name: 'Create draft page',
            description: 'Create a draft-like unpublished page record for an existing site, type, and layout.',
            scope: 'capell.pages.write',
            server: CapabilityServerEnum::Site,
            risk: CapabilityRiskEnum::High,
            actionClass: CreateDraftPageCapabilityAction::class,
            requiredPackage: 'capell-app/core',
            auditEvent: 'capell_mcp.pages.create_draft',
        ));

        $registry->register(new CapabilityData(
            key: 'capell.pages.update_draft',
            name: 'Update draft page',
            description: 'Update safe draft page fields such as name, visibility dates, meta, and admin data.',
            scope: 'capell.pages.write',
            server: CapabilityServerEnum::Site,
            risk: CapabilityRiskEnum::High,
            actionClass: UpdateDraftPageCapabilityAction::class,
            requiredPackage: 'capell-app/core',
            auditEvent: 'capell_mcp.pages.update_draft',
        ));

        $registry->register(new CapabilityData(
            key: 'capell.pages.disable',
            name: 'Disable page',
            description: 'Disable a page by ending its visibility window.',
            scope: 'capell.pages.write',
            server: CapabilityServerEnum::Site,
            risk: CapabilityRiskEnum::High,
            actionClass: DisablePageCapabilityAction::class,
            requiredPackage: 'capell-app/core',
            auditEvent: 'capell_mcp.pages.disable',
        ));

        $registry->register(new CapabilityData(
            key: 'capell.pages.inspect_readiness',
            name: 'Inspect page publishing readiness',
            description: 'Inspect whether a page has the minimum related site, type, layout, and URL state for publishing review.',
            scope: 'capell.pages.read',
            server: CapabilityServerEnum::Site,
            risk: CapabilityRiskEnum::Read,
            actionClass: InspectPagePublishingReadinessCapabilityAction::class,
            requiredPackage: 'capell-app/core',
            requiresConfirmation: false,
            auditEvent: 'capell_mcp.pages.inspect_readiness',
        ));
    }

    private function registerTaggedCapabilityProviders(): void
    {
        $registry = $this->app->make(CapellMcpCapabilityRegistry::class);

        foreach ($this->app->tagged(CapellMcpCapabilityProvider::class) as $provider) {
            if ($provider instanceof CapellMcpCapabilityProvider) {
                $provider->registerCapabilities($registry);
            }
        }
    }
}
