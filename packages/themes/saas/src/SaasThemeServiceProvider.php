<?php

declare(strict_types=1);

namespace Capell\Themes\Saas;

use Capell\Themes\Core\Theme\ThemeRegistrar;
use Capell\Themes\Saas\Console\InstallCommand;
use Capell\Themes\Saas\Widgets\CTABannerWidget;
use Capell\Themes\Saas\Widgets\FAQAccordionWidget;
use Capell\Themes\Saas\Widgets\FeatureMatrixWidget;
use Capell\Themes\Saas\Widgets\HeroWithScreenshotWidget;
use Capell\Themes\Saas\Widgets\IntegrationsGridWidget;
use Capell\Themes\Saas\Widgets\PricingTableWidget;
use Capell\Themes\Saas\Widgets\SaasFooterWidget;
use Capell\Themes\Saas\Widgets\TestimonialsWallWidget;
use Capell\Themes\Saas\Widgets\UseCasesTabsWidget;
use Illuminate\Support\ServiceProvider;

class SaasThemeServiceProvider extends ServiceProvider
{
    public const THEME_KEY = 'saas';

    public const VERSION = '1.0.0';

    /**
     * Return list of widget classes bundled with this theme.
     *
     * @return array<int, class-string>
     */
    public static function widgets(): array
    {
        return [
            HeroWithScreenshotWidget::class,
            FeatureMatrixWidget::class,
            PricingTableWidget::class,
            IntegrationsGridWidget::class,
            UseCasesTabsWidget::class,
            TestimonialsWallWidget::class,
            FAQAccordionWidget::class,
            CTABannerWidget::class,
            SaasFooterWidget::class,
        ];
    }

    /**
     * Register bindings.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/saas.php', 'capell-saas');
    }

    /**
     * Bootstrap the package.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'saas');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        ThemeRegistrar::register('saas', 'SaaS');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('vendor/capell-themes/saas/views'),
            ], 'capell-saas-views');

            $this->publishes([
                __DIR__ . '/../resources/css' => resource_path('vendor/capell-themes/saas/css'),
            ], 'capell-saas-css');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('vendor/capell-themes/saas/views'),
                __DIR__ . '/../resources/css' => resource_path('vendor/capell-themes/saas/css'),
            ], 'capell-saas');

            $this->registerCommands();
        }

        $this->registerMosaicWidgets();
    }

    /**
     * Register artisan console commands provided by the theme.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            InstallCommand::class,
        ]);
    }

    /**
     * Register widgets with Mosaic if it is installed.
     */
    protected function registerMosaicWidgets(): void
    {
        if (! class_exists('Capell\\Mosaic\\Models\\Widget')) {
            return;
        }

        if (! class_exists('Capell\\Mosaic\\Facades\\Mosaic')) {
            return;
        }

        /** @var object $mosaic */
        $mosaic = $this->app->make('mosaic');

        foreach (self::widgets() as $widget) {
            if (method_exists($mosaic, 'registerWidget')) {
                $mosaic->registerWidget(new $widget);
            }
        }
    }
}
