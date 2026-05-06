<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Providers;

use Capell\Admin\Contracts\Dashboard\ContentHealthDataProvider;
use Capell\Admin\Contracts\Dashboard\MyWorkQueueDataProvider;
use Capell\Admin\Contracts\Dashboard\RecentlyPublishedDataProvider;
use Capell\Admin\Contracts\Dashboard\SiteStatsDataProvider;
use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Widgets\Dashboard\MyWorkQueueWidget;
use Capell\Admin\Filament\Widgets\Dashboard\RecentlyPublishedWidget;
use Capell\Core\Events\PageSaved;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\PublishingStudio\Events\WorkspaceStateChanged;
use Capell\PublishingStudio\Filament\Pages\ActivityTrailPage;
use Capell\PublishingStudio\Filament\Pages\ImportPagesPage;
use Capell\PublishingStudio\Filament\Pages\ScheduledPublishingPage;
use Capell\PublishingStudio\Filament\Pages\StaleDraftsPage;
use Capell\PublishingStudio\Filament\Resources\PreviewLinks\PreviewLinkResource;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\WorkspaceResource;
use Capell\PublishingStudio\Filament\Settings\Contributors\DefaultDashboardSettingsContributor;
use Capell\PublishingStudio\Filament\Settings\Contributors\SystemHealthSettingsContributor;
use Capell\PublishingStudio\Filament\Widgets\ContentSchedulerOverviewWidget;
use Capell\PublishingStudio\Filament\Widgets\WorkspaceActivityWidgetAbstract;
use Capell\PublishingStudio\Listeners\SendWorkspaceStateNotification;
use Capell\PublishingStudio\Livewire\DiffPanel;
use Capell\PublishingStudio\Livewire\FieldCommentThread;
use Capell\PublishingStudio\Livewire\WorkspaceApprovalHistory;
use Capell\PublishingStudio\Livewire\WorkspaceContextBanner;
use Capell\PublishingStudio\Livewire\WorkspaceSwitcher;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Policies\WorkspacePolicy;
use Capell\PublishingStudio\Support\Dashboard\WorkspaceContentHealthDataProvider;
use Capell\PublishingStudio\Support\Dashboard\WorkspaceMyWorkQueueDataProvider;
use Capell\PublishingStudio\Support\Dashboard\WorkspaceRecentlyPublishedDataProvider;
use Capell\PublishingStudio\Support\Dashboard\WorkspaceSiteStatsDataProvider;
use Capell\PublishingStudio\WorkspaceContext;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell-publishing-studio');
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'capell-publishing-studio');

        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->registerDashboardSettingsContributors()
            ->registerDashboardDataProviders()
            ->registerFilamentExtensions()
            ->registerLivewireComponents()
            ->registerRenderHooks()
            ->registerEventListeners()
            ->registerPolicies();
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
        $this->app->singleton(ContentHealthDataProvider::class, WorkspaceContentHealthDataProvider::class);
        $this->app->singleton(MyWorkQueueDataProvider::class, WorkspaceMyWorkQueueDataProvider::class);
        $this->app->singleton(RecentlyPublishedDataProvider::class, WorkspaceRecentlyPublishedDataProvider::class);
        $this->app->singleton(SiteStatsDataProvider::class, WorkspaceSiteStatsDataProvider::class);

        return $this;
    }

    private function registerLivewireComponents(): self
    {
        Livewire::component('capell-publishing-studio::workspace-switcher', WorkspaceSwitcher::class);
        Livewire::component('capell-publishing-studio::workspace-context-banner', WorkspaceContextBanner::class);
        Livewire::component('capell-publishing-studio::workspace-approval-history', WorkspaceApprovalHistory::class);
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
        CapellAdmin::registerDashboardWidget(MyWorkQueueWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(RecentlyPublishedWidget::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(WorkspaceActivityWidgetAbstract::class, DashboardEnum::Main);
        CapellAdmin::registerDashboardWidget(ContentSchedulerOverviewWidget::class, DashboardEnum::Main);
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(WorkspaceResource::class, group: 'Workspace'));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(PreviewLinkResource::class, group: 'PreviewLink'));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(ActivityTrailPage::class));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(ImportPagesPage::class));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(ScheduledPublishingPage::class));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(StaleDraftsPage::class));

        return $this;
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
