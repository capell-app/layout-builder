<?php

declare(strict_types=1);

namespace Capell\Blog\Providers;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\ConfiguratorTypeEnum;
use Capell\Admin\Enums\ResourceEnum as AdminResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Blog\Actions\EnsureBlogPublishingSurfaceAction;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Enums\WidgetComponentEnum;
use Capell\Blog\Enums\WidgetConfiguratorEnum;
use Capell\Blog\Filament\Configurators\Articles\ArticlePageConfigurator;
use Capell\Blog\Listeners\AddBlogPagesToNavigation;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Mosaic\Enums\ComponentTypeEnum;
use Capell\Mosaic\Enums\ConfiguratorTypeEnum as LayoutSchemaEnum;
use Capell\Navigation\Events\NavigationCreating;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! CapellCore::getPackage('capell-app/blog')->isInstalled()) {
            return;
        }

        $this->registerResources();
        $this->registerWidgetComponents();
        $this->registerConfigurators();
        $this->registerDefaultPages();
        $this->registerNavigationListener();
    }

    private function registerResources(): void
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: ResourceEnum::Article->value,
            group: AdminResourceEnum::Page->name,
            name: strtolower(ResourceEnum::Article->name),
        ));

        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: ResourceEnum::Tag->value,
            group: ResourceEnum::Tag->name,
        ));
    }

    private function registerWidgetComponents(): void
    {
        CapellCore::registerComponents(ComponentTypeEnum::Widget->name, WidgetComponentEnum::cases());
    }

    private function registerConfigurators(): void
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::configurator(
            class: ArticlePageConfigurator::class,
            group: ConfiguratorTypeEnum::Page->value,
            name: ArticlePageConfigurator::getKey(),
        ));

        foreach (WidgetConfiguratorEnum::cases() as $configurator) {
            $configuratorClass = $configurator->value;

            CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::configurator(
                class: $configuratorClass,
                group: LayoutSchemaEnum::Widget->value,
                name: $configuratorClass::getKey(),
            ));
        }
    }

    private function registerDefaultPages(): void
    {
        CapellAdmin::serving(function (): void {
            CapellCore::addDefaultPage('blog', 'Blog', function (Site $site, ?Collection $languages): void {
                EnsureBlogPublishingSurfaceAction::run($site, $languages);
            });

            CapellCore::addDefaultPage('archives', 'Blog Archives', function (Site $site, ?Collection $languages): void {
                EnsureBlogPublishingSurfaceAction::run($site, $languages);
            });
        });
    }

    private function registerNavigationListener(): void
    {
        Event::listen(NavigationCreating::class, AddBlogPagesToNavigation::class);
    }
}
