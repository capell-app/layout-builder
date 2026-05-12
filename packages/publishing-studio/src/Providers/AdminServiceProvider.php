<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Providers;

use Capell\Admin\Contracts\Dashboard\ContentHealthDataProvider;
use Capell\Admin\Contracts\Dashboard\MyWorkQueueDataProvider;
use Capell\Admin\Contracts\Dashboard\RecentlyPublishedDataProvider;
use Capell\Admin\Contracts\Dashboard\SiteStatsDataProvider;
use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Contracts\Extenders\UserSchemaExtender;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Widgets\Dashboard\MyWorkQueueWidget;
use Capell\Admin\Filament\Widgets\Dashboard\RecentlyPublishedWidget;
use Capell\Admin\Support\Bridges\AdminBridgeRegistrar;
use Capell\Admin\Support\Dashboard\DefaultSiteStatsDataProvider;
use Capell\Admin\Support\Dashboard\NullContentHealthDataProvider;
use Capell\Admin\Support\Dashboard\NullMyWorkQueueDataProvider;
use Capell\Admin\Support\Dashboard\NullRecentlyPublishedDataProvider;
use Capell\Core\Events\PageSaved;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Support\Settings\SettingsGroupMetadata;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\PublishingStudio\Actions\DashboardReports\BuildContentSchedulerEventsAction;
use Capell\PublishingStudio\Bridges\PublishingStudioAdminBridge;
use Capell\PublishingStudio\Contracts\WorkspaceTableActionContributor;
use Capell\PublishingStudio\Data\SchedulerEventData;
use Capell\PublishingStudio\Enums\SchedulerEventTypeEnum;
use Capell\PublishingStudio\Events\WorkspaceStateChanged;
use Capell\PublishingStudio\Extenders\PublishingStudioUserSchemaExtender;
use Capell\PublishingStudio\Filament\Pages\ActivityTrailPage;
use Capell\PublishingStudio\Filament\Pages\ImportPagesPage;
use Capell\PublishingStudio\Filament\Pages\ScheduledPublishingPage;
use Capell\PublishingStudio\Filament\Pages\StaleDraftsPage;
use Capell\PublishingStudio\Filament\Resources\PreviewLinks\PreviewLinkResource;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\WorkspaceResource;
use Capell\PublishingStudio\Filament\Settings\Contributors\DefaultDashboardSettingsContributor;
use Capell\PublishingStudio\Filament\Settings\Contributors\SystemHealthSettingsContributor;
use Capell\PublishingStudio\Filament\Settings\PublishingStudioSettingsSchema;
use Capell\PublishingStudio\Filament\Widgets\WorkspaceActivityWidgetAbstract;
use Capell\PublishingStudio\Listeners\SendWorkspaceStateNotification;
use Capell\PublishingStudio\Livewire\DiffPanel;
use Capell\PublishingStudio\Livewire\FieldCommentThread;
use Capell\PublishingStudio\Livewire\ReleaseWorkspaceSummaryPanel;
use Capell\PublishingStudio\Livewire\WorkspaceApprovalHistory;
use Capell\PublishingStudio\Livewire\WorkspaceContextBanner;
use Capell\PublishingStudio\Livewire\WorkspaceSwitcher;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Policies\WorkspacePolicy;
use Capell\PublishingStudio\Settings\PublishingStudioSettings;
use Capell\PublishingStudio\Support\Dashboard\WorkspaceContentHealthDataProvider;
use Capell\PublishingStudio\Support\Dashboard\WorkspaceMyWorkQueueDataProvider;
use Capell\PublishingStudio\Support\Dashboard\WorkspaceRecentlyPublishedDataProvider;
use Capell\PublishingStudio\Support\Dashboard\WorkspaceSiteStatsDataProvider;
use Capell\PublishingStudio\Support\WorkspaceSchema;
use Capell\PublishingStudio\WorkspaceContext;
use Capell\PublishingStudio\WorkspacePeekPreviewActionContributor;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Throwable;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(
            [WorkspacePeekPreviewActionContributor::class],
            WorkspaceTableActionContributor::TAG,
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell-publishing-studio');
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'capell-publishing-studio');

        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->registerDashboardSettingsContributors()
            ->registerSettingsSchemas()
            ->registerDashboardDataProviders()
            ->registerFilamentExtensions()
            ->registerOverviewStats()
            ->registerLivewireComponents()
            ->registerRenderHooks()
            ->registerEventListeners()
            ->registerPolicies();
    }

    private function registerSettingsSchemas(): self
    {
        /** @var SettingsSchemaRegistry $registry */
        $registry = $this->app->make(SettingsSchemaRegistry::class);

        $registry->registerSettingsClass('publishing_studio', PublishingStudioSettings::class);
        $registry->registerMetadata(new SettingsGroupMetadata(
            group: 'publishing_studio',
            label: 'capell-publishing-studio::workspace.settings.group',
            icon: Heroicon::OutlinedDocumentCheck,
            navigationGroup: 'capell-admin::navigation.group_system',
            navigationSort: 110,
            packageName: PublishingStudioServiceProvider::$packageName,
        ));
        $registry->register('publishing_studio', PublishingStudioSettingsSchema::class);

        return $this;
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(PublishingStudioServiceProvider::$packageName);
    }

    private function registerDashboardSettingsContributors(): self
    {
        $this->app->tag(
            [DefaultDashboardSettingsContributor::class, SystemHealthSettingsContributor::class],
            DashboardSettingsContributor::TAG,
        );

        return $this;
    }

    private function registerDashboardDataProviders(): self
    {
        $this->app->bind(
            ContentHealthDataProvider::class,
            fn (): ContentHealthDataProvider => WorkspaceSchema::isReady()
                ? $this->app->make(WorkspaceContentHealthDataProvider::class)
                : $this->app->make(NullContentHealthDataProvider::class),
        );

        $this->app->bind(
            MyWorkQueueDataProvider::class,
            fn (): MyWorkQueueDataProvider => WorkspaceSchema::isReady()
                ? $this->app->make(WorkspaceMyWorkQueueDataProvider::class)
                : $this->app->make(NullMyWorkQueueDataProvider::class),
        );

        $this->app->bind(
            RecentlyPublishedDataProvider::class,
            fn (): RecentlyPublishedDataProvider => WorkspaceSchema::isReady()
                ? $this->app->make(WorkspaceRecentlyPublishedDataProvider::class)
                : $this->app->make(NullRecentlyPublishedDataProvider::class),
        );

        $this->app->bind(
            SiteStatsDataProvider::class,
            fn (): SiteStatsDataProvider => WorkspaceSchema::isReady()
                ? $this->app->make(WorkspaceSiteStatsDataProvider::class)
                : $this->app->make(DefaultSiteStatsDataProvider::class),
        );

        return $this;
    }

    private function registerLivewireComponents(): self
    {
        Livewire::component('capell-publishing-studio::workspace-switcher', WorkspaceSwitcher::class);
        Livewire::component('capell-publishing-studio::workspace-context-banner', WorkspaceContextBanner::class);
        Livewire::component('capell-publishing-studio::workspace-approval-history', WorkspaceApprovalHistory::class);
        Livewire::component('capell-publishing-studio::release-workspace-summary-panel', ReleaseWorkspaceSummaryPanel::class);
        Livewire::component('capell-publishing-studio::field-comment-thread', FieldCommentThread::class);
        Livewire::component('capell-publishing-studio::diff-panel', DiffPanel::class);

        if (method_exists(Livewire::getFacadeRoot(), 'addNamespace')) {
            Livewire::addNamespace(
                namespace: 'capell-publishing-studio',
                classNamespace: 'Capell\\PublishingStudio\\Livewire',
                classPath: __DIR__ . '/../Livewire',
            );
        }

        return $this;
    }

    private function registerRenderHooks(): self
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
            fn (): string => Blade::render('@livewire($component)', ['component' => WorkspaceSwitcher::class]),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            function (): string {
                $workspace = WorkspaceContext::current();

                if (! ($workspace instanceof Workspace)) {
                    return '';
                }

                $color = ($workspace->color !== null && $workspace->color !== '')
                    ? e($workspace->color)
                    : '#f59e0b';

                return '<div class="fixed inset-x-0 top-0 z-50" style="height:3px;background-color:' . $color . ';pointer-events:none;"></div>';
            },
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            fn (): string => Blade::render('@livewire($component)', ['component' => WorkspaceContextBanner::class]),
        );

        return $this;
    }

    private function registerFilamentExtensions(): self
    {
        if ($this->supportsAdminBridges()) {
            CapellAdmin::registerAdminBridge(PublishingStudioServiceProvider::$packageName, PublishingStudioAdminBridge::class);
            CapellAdmin::bootAdminBridges(PublishingStudioServiceProvider::$packageName);

            return $this;
        }

        $this->app->tag(
            [PublishingStudioUserSchemaExtender::class],
            UserSchemaExtender::TAG,
        );

        CapellAdmin::registerDashboardWidget(MyWorkQueueWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(RecentlyPublishedWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(WorkspaceActivityWidgetAbstract::class, DashboardEnum::Main);
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(WorkspaceResource::class, group: 'Workspace'));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(PreviewLinkResource::class, group: 'PreviewLink'));
        CapellAdmin::registerExtensionPage(PublishingStudioServiceProvider::$packageName, ActivityTrailPage::class);
        CapellAdmin::registerExtensionPage(PublishingStudioServiceProvider::$packageName, ImportPagesPage::class);
        CapellAdmin::registerExtensionPage(PublishingStudioServiceProvider::$packageName, ScheduledPublishingPage::class);
        CapellAdmin::registerExtensionPage(PublishingStudioServiceProvider::$packageName, StaleDraftsPage::class);

        return $this;
    }

    private function supportsAdminBridges(): bool
    {
        try {
            $admin = CapellAdmin::getFacadeRoot();
        } catch (Throwable) {
            return false;
        }

        return is_object($admin)
            && method_exists($admin, 'registerAdminBridge')
            && method_exists($admin, 'bootAdminBridges')
            && class_exists(PublishingStudioAdminBridge::class)
            && class_exists(AdminBridgeRegistrar::class)
            && method_exists(AdminBridgeRegistrar::class, 'schemaExtender')
            && method_exists(AdminBridgeRegistrar::class, 'dashboardWidget')
            && method_exists(AdminBridgeRegistrar::class, 'resource')
            && method_exists(AdminBridgeRegistrar::class, 'extensionPage');
    }

    private function registerOverviewStats(): self
    {
        foreach (SchedulerEventTypeEnum::cases() as $eventType) {
            CapellAdmin::registerOverviewStat(
                key: 'content_scheduler.' . $eventType->value,
                label: fn (): string => $eventType->getLabel(),
                value: fn (): int => $this->contentSchedulerCount($eventType),
                group: fn (): string => __('capell-publishing-studio::scheduler.title'),
                color: $eventType->getColor(),
                sort: 160 + $this->contentSchedulerSort($eventType),
                settingsKey: 'content_scheduler',
                settingsLabel: fn (): string => __('capell-publishing-studio::scheduler.title'),
            );
        }

        return $this;
    }

    private function contentSchedulerCount(SchedulerEventTypeEnum $eventType): int
    {
        return BuildContentSchedulerEventsAction::run()
            ->filter(fn (SchedulerEventData $event): bool => $event->eventType === $eventType)
            ->count();
    }

    private function contentSchedulerSort(SchedulerEventTypeEnum $eventType): int
    {
        return match ($eventType) {
            SchedulerEventTypeEnum::Publish => 1,
            SchedulerEventTypeEnum::Unpublish => 2,
            SchedulerEventTypeEnum::Embargo => 3,
            SchedulerEventTypeEnum::ReviewReminder => 4,
        };
    }

    private function registerEventListeners(): self
    {
        Event::listen(WorkspaceStateChanged::class, SendWorkspaceStateNotification::class);

        $clearMergeHistoryCache = static function (): void {
            Cache::forget('dashboard:workspace-merge-history');
        };

        Event::listen(PageSaved::class, $clearMergeHistoryCache);
        Page::created($clearMergeHistoryCache);

        return $this;
    }

    private function registerPolicies(): self
    {
        Gate::policy(Workspace::class, WorkspacePolicy::class);

        return $this;
    }
}
