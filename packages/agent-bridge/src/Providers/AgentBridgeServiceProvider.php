<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Providers;

use Capell\Admin\Contracts\AdminTools\AdminToolItem;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\AgentBridge\Actions\Cache\ClearCapellCacheCapabilityAction;
use Capell\AgentBridge\Actions\Pages\CreateDraftPageCapabilityAction;
use Capell\AgentBridge\Actions\Pages\DisablePageCapabilityAction;
use Capell\AgentBridge\Actions\Pages\InspectPagePublishingReadinessCapabilityAction;
use Capell\AgentBridge\Actions\Pages\UpdateDraftPageCapabilityAction;
use Capell\AgentBridge\Contracts\CapellAgentBridgeCapabilityProvider;
use Capell\AgentBridge\Data\CapabilityData;
use Capell\AgentBridge\Enums\CapabilityRiskEnum;
use Capell\AgentBridge\Enums\CapabilityServerEnum;
use Capell\AgentBridge\Filament\Pages\CapellAgentBridgePromptBuilderPage;
use Capell\AgentBridge\Support\AdminTools\PromptBuilderAdminTool;
use Capell\AgentBridge\Support\CapellAgentBridgeCapabilityRegistry;
use Capell\AgentBridge\Tools\Boost\ListBoostCapabilitiesTool;
use Capell\AgentBridge\Tools\Boost\PreviewBoostCapabilityTool;
use Capell\Core\Facades\CapellCore;
use Filament\Pages\Page;
use Illuminate\Support\ServiceProvider;
use Laravel\Boost\AgentBridge\Boost;

final class AgentBridgeServiceProvider extends ServiceProvider
{
    public static string $packageName = 'capell-app/agent-bridge';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/capell-agent-bridge.php', 'capell-agent-bridge');

        $this->registerPackageMetadata();
    }

    public function boot(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->app->singleton(CapellAgentBridgeCapabilityRegistry::class, fn (): CapellAgentBridgeCapabilityRegistry => new CapellAgentBridgeCapabilityRegistry);

        $this->publishes([
            __DIR__ . '/../../config/capell-agent-bridge.php' => config_path('capell-agent-bridge.php'),
        ], 'capell-agent-bridge-config');

        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'capell-agent-bridge');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell-agent-bridge');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/agent-bridge.php');

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

        $includedTools = config('boost.agent-bridge.tools.include', []);

        config([
            'boost.agent-bridge.tools.include' => array_values(array_unique([
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
        $promptBuilderPage = CapellAgentBridgePromptBuilderPage::class;
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
        $registry = $this->app->make(CapellAgentBridgeCapabilityRegistry::class);

        $registry->register(new CapabilityData(
            key: 'capell.cache.clear',
            name: 'Clear Capell caches',
            description: 'Clear Capell admin, frontend, schema, component, and application caches where commands are available.',
            scope: 'capell.cache.run',
            server: CapabilityServerEnum::Site,
            risk: CapabilityRiskEnum::Medium,
            actionClass: ClearCapellCacheCapabilityAction::class,
            requiredPackage: 'capell-app/core',
            auditEvent: 'capell_agent-bridge.cache.clear',
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
            auditEvent: 'capell_agent-bridge.pages.create_draft',
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
            auditEvent: 'capell_agent-bridge.pages.update_draft',
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
            auditEvent: 'capell_agent-bridge.pages.disable',
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
            auditEvent: 'capell_agent-bridge.pages.inspect_readiness',
        ));
    }

    private function registerTaggedCapabilityProviders(): void
    {
        $registry = $this->app->make(CapellAgentBridgeCapabilityRegistry::class);

        foreach ($this->app->tagged(CapellAgentBridgeCapabilityProvider::class) as $provider) {
            if ($provider instanceof CapellAgentBridgeCapabilityProvider) {
                $provider->registerCapabilities($registry);
            }
        }
    }
}
