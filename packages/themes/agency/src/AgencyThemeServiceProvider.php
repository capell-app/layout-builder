<?php

declare(strict_types=1);

namespace Capell\Themes\Agency;

use Capell\Themes\Agency\Widgets\AgencyFooterWidget;
use Capell\Themes\Agency\Widgets\AwardsBadgesWidget;
use Capell\Themes\Agency\Widgets\ClientsMarqueeWidget;
use Capell\Themes\Agency\Widgets\ContactInquiryWidget;
use Capell\Themes\Agency\Widgets\HeroStatementWidget;
use Capell\Themes\Agency\Widgets\PortfolioGridWidget;
use Capell\Themes\Agency\Widgets\ProcessFlowWidget;
use Capell\Themes\Agency\Widgets\ServicesShowcaseWidget;
use Capell\Themes\Agency\Widgets\TestimonialsQuoteWidget;
use Illuminate\Support\ServiceProvider;

class AgencyThemeServiceProvider extends ServiceProvider
{
    public const THEME_KEY = 'agency';

    public const VERSION = '1.0.0';

    /**
     * Register bindings.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/agency.php', 'capell-agency');
    }

    /**
     * Bootstrap the package.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'agency');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('vendor/capell-themes/agency/views'),
            ], 'capell-agency-views');

            $this->publishes([
                __DIR__.'/../resources/css' => resource_path('vendor/capell-themes/agency/css'),
            ], 'capell-agency-css');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('vendor/capell-themes/agency/views'),
                __DIR__.'/../resources/css' => resource_path('vendor/capell-themes/agency/css'),
            ], 'capell-agency');

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
            \Capell\Themes\Agency\Console\InstallCommand::class,
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

        $widgets = static::widgets();

        if (! class_exists('Capell\\Mosaic\\Facades\\Mosaic')) {
            return;
        }

        /** @var \Capell\Mosaic\Facades\Mosaic $mosaic */
        $mosaic = $this->app->make('mosaic');

        foreach ($widgets as $widget) {
            if (method_exists($mosaic, 'registerWidget')) {
                $mosaic->registerWidget(new $widget);
            }
        }
    }

    /**
     * Return list of widget classes bundled with this theme.
     *
     * @return array<int, class-string>
     */
    public static function widgets(): array
    {
        return [
            HeroStatementWidget::class,
            PortfolioGridWidget::class,
            ProcessFlowWidget::class,
            ServicesShowcaseWidget::class,
            ClientsMarqueeWidget::class,
            TestimonialsQuoteWidget::class,
            AwardsBadgesWidget::class,
            ContactInquiryWidget::class,
            AgencyFooterWidget::class,
        ];
    }
}
