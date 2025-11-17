<?php

declare(strict_types=1);

namespace Capell\Tests\Fixtures\Support\Filament;

use Capell\Admin\Enums\FilamentColorEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\CapellAdminPlugin;
use Capell\Admin\Filament\Widgets\AlertsWidget;
use Capell\Admin\Filament\Widgets\AuthenticationLogsWidget;
use Capell\Admin\Filament\Widgets\LatestPagesWidget;
use Capell\Admin\Filament\Widgets\PopularPagesWidget;
use Capell\Admin\Filament\Widgets\TotalPageViewsChartWidget;
use Capell\Admin\Filament\Widgets\TotalVisitorsChartWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors(FilamentColorEnum::colors())
            ->assets([
                Css::make('capell-panel', asset('css/capell-app/admin/capell-panel.css')),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->plugins([
                CapellAdminPlugin::make(),
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AlertsWidget::class,
                AuthenticationLogsWidget::class,
                PopularPagesWidget::class,
                LatestPagesWidget::class,
                TotalPageViewsChartWidget::class,
                TotalVisitorsChartWidget::class,
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->navigationItems(CapellAdmin::getNavigationItems())
            ->navigationGroups(CapellAdmin::getNavigationGroups())
            ->sidebarFullyCollapsibleOnDesktop()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
