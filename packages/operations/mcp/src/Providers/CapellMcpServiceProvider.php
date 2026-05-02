<?php

declare(strict_types=1);

namespace Capell\Mcp\Providers;

use Capell\Mcp\Actions\Cache\ClearCapellCacheCapabilityAction;
use Capell\Mcp\Actions\Pages\CreateDraftPageCapabilityAction;
use Capell\Mcp\Actions\Pages\DisablePageCapabilityAction;
use Capell\Mcp\Actions\Pages\InspectPagePublishingReadinessCapabilityAction;
use Capell\Mcp\Actions\Pages\UpdateDraftPageCapabilityAction;
use Capell\Mcp\Contracts\CapellMcpCapabilityProvider;
use Capell\Mcp\Data\CapabilityData;
use Capell\Mcp\Enums\CapabilityRiskEnum;
use Capell\Mcp\Enums\CapabilityServerEnum;
use Capell\Mcp\Support\CapellMcpCapabilityRegistry;
use Illuminate\Support\ServiceProvider;

final class CapellMcpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/capell-mcp.php', 'capell-mcp');

        $this->app->singleton(CapellMcpCapabilityRegistry::class, fn (): CapellMcpCapabilityRegistry => new CapellMcpCapabilityRegistry);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/capell-mcp.php' => config_path('capell-mcp.php'),
        ], 'capell-mcp-config');

        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'capell-mcp');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell-mcp');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/mcp.php');

        $this->registerAdminIntegration();
        $this->registerBuiltInCapabilities();
        $this->registerTaggedCapabilityProviders();
    }

    private function registerAdminIntegration(): void
    {
        $adminFacade = 'Capell\\Admin\\Facades\\CapellAdmin';
        $adminToolItem = 'Capell\\Admin\\Contracts\\AdminTools\\AdminToolItem';
        $filamentPage = 'Filament\\Pages\\Page';
        $promptBuilderPage = 'Capell\\Mcp\\Filament\\Pages\\CapellMcpPromptBuilderPage';
        $promptBuilderTool = 'Capell\\Mcp\\Support\\AdminTools\\PromptBuilderAdminTool';

        if (! class_exists($adminFacade) || ! interface_exists($adminToolItem) || ! class_exists($filamentPage)) {
            return;
        }

        if (! class_exists($promptBuilderPage) || ! class_exists($promptBuilderTool)) {
            return;
        }

        $adminFacade::registerPage($promptBuilderPage);

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
