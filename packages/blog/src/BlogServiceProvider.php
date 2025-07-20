<?php

declare(strict_types=1);

namespace Capell\Blog;

use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Enums\SchemaEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Blog\Actions\InstallBlogPackageAction;
use Capell\Blog\Commands\DemoCommand;
use Capell\Blog\Enums\BlogModelEnum;
use Capell\Blog\Enums\BlogResourceEnum;
use Capell\Blog\Enums\WidgetComponentEnum;
use Capell\Blog\Filament\Resources\ArticleResource;
use Capell\Blog\Filament\Schemas\Page\ArticlePageSchema;
use Capell\Blog\Listeners\AddBlogPagesToNavigation;
use Capell\Blog\Models\Article;
use Capell\Blog\Services\BlogCreator;
use Capell\Blog\Services\Loader\BlogLoader;
use Capell\Blog\Services\Sitemap\ArchivePageSitemap;
use Capell\Core\Events\NavigationCreating;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Packages\AbstractPackageServiceProvider;
use Capell\Layout\Enums\ComponentTypeEnum;
use Composer\InstalledVersions;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;

class BlogServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-blog';

    public static string $description = 'Article page type with blog archives.';

    public function bootingPackage(): void
    {
        Blade::componentNamespace('Capell\\Blog\\View\\Components', 'capell-blog');

        foreach (config('capell-blog.livewire_components', []) as $name => $class) {
            Livewire::component($name, $class);
        }

        CapellCore::registerModel(BlogModelEnum::Article, Article::class);

        Relation::morphMap([
            'article' => Article::class,
        ]);

        CapellCore::addSitemapPages('archives', ArchivePageSitemap::class);

        if ($this->app->runningInConsole() && (class_exists(AboutCommand::class) && class_exists(InstalledVersions::class))) {
            AboutCommand::add('Capell', [
                self::$name => fn () => InstalledVersions::getPrettyVersion('capell-app/blog'),
            ]);
        }

        Event::listen(
            NavigationCreating::class,
            AddBlogPagesToNavigation::class,
        );

        CapellAdmin::serving(function (): void {
            CapellCore::addDefaultPage('blog', 'Blog', function ($site, $languages): void {
                BlogCreator::createBlogPage($site, languages: $languages);
            });

            CapellCore::addDefaultPage('archives', 'Blog Archives', function ($site, $languages): void {
                $blogPage = BlogLoader::getBlogPage($site);

                $archivesPage = BlogCreator::createArchivesPage($site, $blogPage, languages: $languages);

                BlogCreator::createArchivePage($site, $archivesPage, languages: $languages);
            });
        });
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile()
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasCommands([
                DemoCommand::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->startWith(function (InstallCommand $command): void {
                    $command->info('Installing Capell Blog Package...');
                    InstallBlogPackageAction::run();
                });
            });
    }

    public function registeringPackage(): void
    {
        parent::registeringPackage();

        CapellCore::registerPackage(
            self::$name,
            class: self::class,
            path: __DIR__,
            sort: 9,
            permissions: $this->getPackagePermissions(),
            demoCommand: true,
            demoParams: ['author', 'sites'],
        );

        CapellAdmin::registerResource(
            ResourceEnum::Page,
            class: ArticleResource::class,
            name: BlogResourceEnum::Article->name
        );

        CapellCore::registerComponents(ComponentTypeEnum::Widget->value, WidgetComponentEnum::cases());

        CapellAdmin::registerSchema(SchemaEnum::Page, ArticlePageSchema::class);
    }

    private function getPackagePermissions(): array
    {
        return [
            'create_article',
            'replicate_article',
            'reorder_article',
            'restore_any_article',
            'restore_article',
            'update_article',
            'view_any_article',
            'view_article',
        ];
    }
}
