<?php

declare(strict_types=1);

use Capell\Admin\Filament\Pages\CapellDashboard;
use Capell\Admin\Filament\Widgets\Dashboard\AbstractCapellInfoWidget;
use Capell\Admin\Filament\Widgets\Dashboard\ListPagesWidget;
use Capell\Admin\Filament\Widgets\Dashboard\MyWorkQueueWidgetAbstract;
use Capell\Admin\Filament\Widgets\Dashboard\RecentlyPublishedWidgetAbstract;
use Capell\Admin\Filament\Widgets\Dashboard\SiteStatsOverviewWidget;
use Capell\Admin\Filament\Widgets\Dashboard\SiteTrafficWidget;
use Capell\Admin\Filament\Widgets\Dashboard\TopPagesWidget;
use Capell\Admin\Filament\Widgets\Health\AuthenticationLogsWidget;
use Capell\Admin\Filament\Widgets\Health\SiteHealthWidgetAbstract;
use Capell\Admin\Filament\Widgets\Health\TotalAccessLogsWidget;
use Capell\Core\Models\SiteDomain;
use Capell\Tests\Fixtures\Models\User;
use Capell\Workspaces\Filament\Widgets\WorkspaceActivityWidgetAbstract;
use Filament\Widgets\FilamentInfoWidget;

it('getColumns returns 3', function (): void {
    $dashboard = new CapellDashboard;
    expect($dashboard->getColumns())->toBe(3);
});

it('getWidgets contains all expected widget classes', function (): void {
    SiteDomain::factory()->default()->create();

    $dashboard = new CapellDashboard;
    $widgets = $dashboard->getWidgets();

    expect($widgets)
        ->toContain(SiteStatsOverviewWidget::class)
        ->toContain(SiteTrafficWidget::class)
        ->toContain(TopPagesWidget::class)
        ->toContain(WorkspaceActivityWidgetAbstract::class)
        ->toContain(MyWorkQueueWidgetAbstract::class)
        ->toContain(RecentlyPublishedWidgetAbstract::class)
        ->toContain(AbstractCapellInfoWidget::class)
        ->toContain(SiteHealthWidgetAbstract::class)
        ->toContain(FilamentInfoWidget::class);
});

it('getWidgets does not contain dropped widgets', function (): void {
    $dashboard = new CapellDashboard;
    $widgets = $dashboard->getWidgets();

    expect($widgets)
        ->not->toContain(AuthenticationLogsWidget::class)
        ->not->toContain(TotalAccessLogsWidget::class)
        ->not->toContain(ListPagesWidget::class);
});

it('isSystemHealthLinkVisible returns false for unauthenticated users', function (): void {
    $dashboard = new CapellDashboard;
    expect($dashboard->isSystemHealthLinkVisible())->toBeFalse();
});

it('isSystemHealthLinkVisible returns false for regular users without super_admin role', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $dashboard = new CapellDashboard;
    expect($dashboard->isSystemHealthLinkVisible())->toBeFalse();
});

it('isSystemHealthLinkVisible returns true for super_admin users', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $roleName = config('capell.roles.super_admin', 'super_admin');

    try {
        $user->assignRole($roleName);
    } catch (BadMethodCallException) {
        $this->markTestSkipped('User model does not support roles.');
    }

    $dashboard = new CapellDashboard;
    expect($dashboard->isSystemHealthLinkVisible())->toBeTrue();
});
