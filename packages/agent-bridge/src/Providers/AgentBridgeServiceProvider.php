<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Providers;

use Capell\Admin\Actions\Users\ShouldLoadUserResourceBridgeAction;
use Capell\Admin\Contracts\Extenders\UserSchemaExtender;
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
use Capell\AgentBridge\Extenders\AgentBridgeUserSchemaExtender;
use Capell\AgentBridge\Filament\Pages\CapellAgentBridgePromptBuilderPage;
use Capell\AgentBridge\Filament\Settings\AgentBridgeSettingsSchema;
use Capell\AgentBridge\Settings\AgentBridgeSettings;
use Capell\AgentBridge\Support\CapellAgentBridgeCapabilityRegistry;
use Capell\AgentBridge\Tools\Boost\ListBoostCapabilitiesTool;
use Capell\AgentBridge\Tools\Boost\PreviewBoostCapabilityTool;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Settings\SettingsGroupMetadata;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Filament\Pages\Page;
use Filament\Support\Enums\IconSize;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Laravel\Boost\AgentBridge\Boost;

final class AgentBridgeServiceProvider extends ServiceProvider
{
    public static string $packageName = 'capell-app/agent-bridge';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/capell-agent-bridge.php', 'capell-agent-bridge');

        $this->registerPackageMetadata();
        $this->registerSettingsIntegration();
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

        $this->registerSettingsIntegration();
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

        $packagePath = realpath(__DIR__ . '/../..');

        CapellCore::registerPackage(
            name: self::$packageName,
            path: is_string($packagePath) ? $packagePath : null,
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
        $filamentPage = Page::class;
        $promptBuilderPage = CapellAgentBridgePromptBuilderPage::class;

        if (! class_exists($adminFacade) || ! class_exists($filamentPage)) {
            return;
        }

        if (! class_exists($promptBuilderPage)) {
            return;
        }

        $adminFacade::registerExtensionPage(self::$packageName, $promptBuilderPage);
        $this->registerUserResourceBridge();

        if (! class_exists(FilamentView::class) || ! class_exists(PanelsRenderHook::class)) {
            return;
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            fn (): string => Blade::render(
                <<<'BLADE'
                    <a
                        class="text-gray-600 hover:text-primary-600 focus:text-primary-600 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-md transition-colors focus:outline-none dark:text-gray-300 dark:hover:text-primary-400 dark:focus:text-primary-400"
                        href="{{ $url }}"
                        title="{{ $label }}"
                        aria-label="{{ $label }}"
                    >
                        @svg($icon, 'h-5 w-5')
                    </a>
                BLADE,
                [
                    'icon' => Heroicon::OutlinedSparkles->getIconForSize(IconSize::Small),
                    'label' => __('capell-agent-bridge::admin.prompt_builder_tool'),
                    'url' => $promptBuilderPage::getUrl(),
                ],
            ),
        );
    }

    private function registerSettingsIntegration(): void
    {
        $settings = config('settings.settings', []);

        if (! in_array(AgentBridgeSettings::class, $settings, true)) {
            $settings[] = AgentBridgeSettings::class;
        }

        config(['settings.settings' => $settings]);

        if (! class_exists(SettingsSchemaRegistry::class)) {
            return;
        }

        if (! $this->app->bound(SettingsSchemaRegistry::class)) {
            $this->app->afterResolving(
                SettingsSchemaRegistry::class,
                fn (SettingsSchemaRegistry $registry): SettingsSchemaRegistry => $this->registerSettingsSchemas($registry),
            );

            return;
        }

        /** @var SettingsSchemaRegistry $registry */
        $registry = $this->app->make(SettingsSchemaRegistry::class);

        $this->registerSettingsSchemas($registry);
    }

    private function registerSettingsSchemas(SettingsSchemaRegistry $registry): SettingsSchemaRegistry
    {
        $registry->registerSettingsClass(AgentBridgeSettings::group(), AgentBridgeSettings::class);
        $registry->register(AgentBridgeSettings::group(), AgentBridgeSettingsSchema::class);

        if (class_exists(SettingsGroupMetadata::class)) {
            $registry->registerMetadata(new SettingsGroupMetadata(
                group: AgentBridgeSettings::group(),
                label: 'capell-agent-bridge::admin.settings_title',
                icon: Heroicon::OutlinedSparkles,
                navigationGroup: 'capell-admin::navigation.group_administration',
                navigationSort: 94,
                packageName: self::$packageName,
            ));
        }

        return $registry;
    }

    private function registerUserResourceBridge(): void
    {
        if (
            ! interface_exists(UserSchemaExtender::class)
            || ! class_exists(ShouldLoadUserResourceBridgeAction::class)
        ) {
            return;
        }

        $this->app->bind(AgentBridgeUserSchemaExtender::class);
        $this->app->tag([AgentBridgeUserSchemaExtender::class], UserSchemaExtender::TAG);
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
