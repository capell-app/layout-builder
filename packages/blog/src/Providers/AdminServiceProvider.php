<?php

declare(strict_types=1);

namespace Capell\Blog\Providers;

use Capell\Admin\Enums\ConfiguratorTypeEnum;
use Capell\Admin\Enums\ResourceEnum as AdminResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Enums\WidgetComponentEnum;
use Capell\Blog\Enums\WidgetConfiguratorEnum;
use Capell\Blog\Filament\Configurators\Articles\ArticlePageConfigurator;
use Capell\Blog\Listeners\AddBlogPagesToNavigation;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Mosaic\Enums\ComponentTypeEnum;
use Capell\Mosaic\Enums\ConfiguratorTypeEnum as LayoutSchemaEnum;
use Capell\Navigation\Events\NavigationCreating;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerResources();
    }

    public function boot(): void
    {
        if (! CapellCore::getPackage('capell-app/blog')->isInstalled()) {
            return;
        }

        $this->registerWidgetComponents();
        $this->registerConfigurators();
        $this->registerDefaultPages();
        $this->registerNavigationListener();
    }

    private function registerResources(): void
    {
        CapellAdmin::registerResource(
            AdminResourceEnum::Page,
            class: ResourceEnum::Article->value,
            name: strtolower(ResourceEnum::Article->name),
        );

        CapellAdmin::registerResource(ResourceEnum::Tag->name, class: ResourceEnum::Tag->value);
    }

    private function registerWidgetComponents(): void
    {
        CapellCore::registerComponents(ComponentTypeEnum::Widget->name, WidgetComponentEnum::cases());
    }

    private function registerConfigurators(): void
    {
        CapellAdmin::registerConfigurator(ConfiguratorTypeEnum::Page, ArticlePageConfigurator::class);

        foreach (WidgetConfiguratorEnum::cases() as $configurators) {
            CapellAdmin::registerConfigurator(LayoutSchemaEnum::Widget, $configurators->value);
        }
    }

    private function registerDefaultPages(): void
    {
        CapellAdmin::serving(function (): void {
            CapellCore::addDefaultPage('blog', 'Blog', function (Site $site, ?Type $languages): void {
                (new BlogCreator)->createBlogPage($site, languages: $languages);
            });

            CapellCore::addDefaultPage('archives', 'Blog Archives', function (Site $site, ?Type $languages): void {
                $blogPage = BlogLoader::getBlogPage($site);
                $archivesPage = (new BlogCreator)->createArchivesPage($blogPage, languages: $languages);
                (new BlogCreator)->createArchivePage($archivesPage, languages: $languages);
            });
        });
    }

    private function registerNavigationListener(): void
    {
        Event::listen(NavigationCreating::class, AddBlogPagesToNavigation::class);
    }
}
