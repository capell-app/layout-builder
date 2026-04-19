<?php

declare(strict_types=1);

namespace Capell\Themes\Corporate;

use Capell\Mosaic\Facades\Mosaic;
use Capell\Themes\Core\Theme\ThemeRegistrar;
use Capell\Themes\Corporate\Console\InstallCommand;
use Capell\Themes\Corporate\Widgets\BlogListingWidget;
use Capell\Themes\Corporate\Widgets\CaseStudiesCarouselWidget;
use Capell\Themes\Corporate\Widgets\ContactFormWidget;
use Capell\Themes\Corporate\Widgets\FeaturesGridWidget;
use Capell\Themes\Corporate\Widgets\FooterWidget;
use Capell\Themes\Corporate\Widgets\HeroSectionWidget;
use Capell\Themes\Corporate\Widgets\TeamGridWidget;
use Illuminate\Support\ServiceProvider;

class CorporateThemeServiceProvider extends ServiceProvider
{
    public const THEME_KEY = 'corporate';

    public const VERSION = '1.0.0';

    /**
     * Return list of widget classes bundled with this theme.
     *
     * @return array<int, class-string>
     */
    public static function widgets(): array
    {
        return [
            HeroSectionWidget::class,
            FeaturesGridWidget::class,
            TeamGridWidget::class,
            CaseStudiesCarouselWidget::class,
            BlogListingWidget::class,
            ContactFormWidget::class,
            FooterWidget::class,
        ];
    }

    /**
     * Register bindings.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/corporate.php', 'capell-corporate');
    }

    /**
     * Bootstrap the package.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'corporate');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        ThemeRegistrar::register('corporate', 'Corporate');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('vendor/capell-themes/corporate/views'),
            ], 'capell-corporate-views');

            $this->publishes([
                __DIR__ . '/../resources/css' => resource_path('vendor/capell-themes/corporate/css'),
            ], 'capell-corporate-css');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('vendor/capell-themes/corporate/views'),
                __DIR__ . '/../resources/css' => resource_path('vendor/capell-themes/corporate/css'),
            ], 'capell-corporate');

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

        $widgets = [
            HeroSectionWidget::class,
            FeaturesGridWidget::class,
            TeamGridWidget::class,
            CaseStudiesCarouselWidget::class,
            BlogListingWidget::class,
            ContactFormWidget::class,
            FooterWidget::class,
        ];

        if (! class_exists('Capell\\Mosaic\\Facades\\Mosaic')) {
            return;
        }

        /** @var Mosaic $mosaic */
        $mosaic = $this->app->make('mosaic');

        foreach ($widgets as $widget) {
            if (method_exists($mosaic, 'registerWidget')) {
                $mosaic->registerWidget(new $widget);
            }
        }
    }
}
