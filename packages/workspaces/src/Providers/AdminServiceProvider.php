<?php

declare(strict_types=1);

namespace Capell\Workspaces\Providers;

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Workspaces\Events\WorkspaceStateChanged;
use Capell\Workspaces\Filament\Settings\Contributors\DefaultDashboardSettingsContributor;
use Capell\Workspaces\Filament\Settings\Contributors\SystemHealthSettingsContributor;
use Capell\Workspaces\Filament\Widgets\WorkspaceActivityWidgetAbstract;
use Capell\Workspaces\Listeners\SendWorkspaceStateNotification;
use Capell\Workspaces\Livewire\DiffPanel;
use Capell\Workspaces\Livewire\FieldCommentThread;
use Capell\Workspaces\Livewire\PageApprovalStatus;
use Capell\Workspaces\Livewire\PublishStatusPanel;
use Capell\Workspaces\Livewire\WorkspaceApprovalHistory;
use Capell\Workspaces\Livewire\WorkspaceContextBanner;
use Capell\Workspaces\Livewire\WorkspaceSwitcher;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Policies\WorkspacePolicy;
use Capell\Workspaces\WorkspaceContext;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(
            [DefaultDashboardSettingsContributor::class, SystemHealthSettingsContributor::class],
            DashboardSettingsContributor::TAG,
        );
    }

    public function boot(): void
    {
        $this->registerLivewireComponents()
            ->registerRenderHooks()
            ->registerDashboardWidgets()
            ->registerEventListeners()
            ->registerPolicies();
    }

    private function registerLivewireComponents(): self
    {
        Livewire::component('capell-workspaces::workspace-switcher', WorkspaceSwitcher::class);
        Livewire::component('capell-workspaces::workspace-context-banner', WorkspaceContextBanner::class);
        Livewire::component('capell-workspaces::workspace-approval-history', WorkspaceApprovalHistory::class);
        Livewire::component('capell-workspaces::field-comment-thread', FieldCommentThread::class);
        Livewire::component('capell-workspaces::diff-panel', DiffPanel::class);
        Livewire::component('capell-admin::publish-status-panel', PublishStatusPanel::class);
        Livewire::component('capell-admin::page-approval-status', PageApprovalStatus::class);

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

    private function registerDashboardWidgets(): self
    {
        CapellAdmin::registerDashboardWidget(WorkspaceActivityWidgetAbstract::class, DashboardEnum::Main);

        return $this;
    }

    private function registerEventListeners(): self
    {
        Event::listen(WorkspaceStateChanged::class, SendWorkspaceStateNotification::class);

        return $this;
    }

    private function registerPolicies(): self
    {
        Gate::policy(Workspace::class, WorkspacePolicy::class);

        return $this;
    }
}
