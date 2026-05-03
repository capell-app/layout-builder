<?php

declare(strict_types=1);

namespace Capell\Workspaces\Providers;

use Capell\Admin\Contracts\Dashboard\MyWorkQueueDataProvider;
use Capell\Admin\Contracts\Dashboard\RecentlyPublishedDataProvider;
use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Widgets\Dashboard\MyWorkQueueWidget;
use Capell\Admin\Filament\Widgets\Dashboard\RecentlyPublishedWidget;
use Capell\Core\Events\PageSaved;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Workspaces\Events\WorkspaceStateChanged;
use Capell\Workspaces\Filament\Pages\ActivityTrailPage;
use Capell\Workspaces\Filament\Pages\ImportPagesPage;
use Capell\Workspaces\Filament\Pages\ScheduledPublishingPage;
use Capell\Workspaces\Filament\Pages\StaleDraftsPage;
use Capell\Workspaces\Filament\Resources\PreviewLinks\PreviewLinkResource;
use Capell\Workspaces\Filament\Resources\Workspaces\WorkspaceResource;
use Capell\Workspaces\Filament\Settings\Contributors\DefaultDashboardSettingsContributor;
use Capell\Workspaces\Filament\Settings\Contributors\SystemHealthSettingsContributor;
use Capell\Workspaces\Filament\Widgets\ContentSchedulerOverviewWidget;
use Capell\Workspaces\Filament\Widgets\WorkspaceActivityWidgetAbstract;
use Capell\Workspaces\Listeners\SendWorkspaceStateNotification;
use Capell\Workspaces\Livewire\DiffPanel;
use Capell\Workspaces\Livewire\FieldCommentThread;
use Capell\Workspaces\Livewire\WorkspaceApprovalHistory;
use Capell\Workspaces\Livewire\WorkspaceContextBanner;
use Capell\Workspaces\Livewire\WorkspaceSwitcher;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Policies\WorkspacePolicy;
use Capell\Workspaces\Support\Dashboard\WorkspaceMyWorkQueueDataProvider;
use Capell\Workspaces\Support\Dashboard\WorkspaceRecentlyPublishedDataProvider;
use Capell\Workspaces\WorkspaceContext;
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
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell-workspaces');
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'capell-workspaces');

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
        return CapellCore::isPackageInstalled(WorkspacesServiceProvider::$packageName);
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
        $this->app->singleton(MyWorkQueueDataProvider::class, WorkspaceMyWorkQueueDataProvider::class);
        $this->app->singleton(RecentlyPublishedDataProvider::class, WorkspaceRecentlyPublishedDataProvider::class);

        return $this;
    }

    private function registerLivewireComponents(): self
    {
        Livewire::component('capell-workspaces::workspace-switcher', WorkspaceSwitcher::class);
        Livewire::component('capell-workspaces::workspace-context-banner', WorkspaceContextBanner::class);
        Livewire::component('capell-workspaces::workspace-approval-history', WorkspaceApprovalHistory::class);
        Livewire::component('capell-workspaces::field-comment-thread', FieldCommentThread::class);
        Livewire::component('capell-workspaces::diff-panel', DiffPanel::class);
        Livewire::addNamespace(
            namespace: 'capell-workspaces',
            classNamespace: 'Capell\\Workspaces\\Livewire',
            classPath: __DIR__ . '/../Livewire',
        );

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
