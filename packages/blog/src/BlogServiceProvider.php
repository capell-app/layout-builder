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
use Capell\Blog\Filament\Resources;
use Capell\Blog\Filament\Schemas;
use Capell\Blog\Services\BlogCreator;
use Capell\Blog\Services\Loader\BlogLoader;
use Capell\Blog\Services\Sitemap\ArchivePageSitemap;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Packages\AbstractPackageServiceProvider;
use Composer\InstalledVersions;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;

class BlogServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-blog';

    public static string $description = 'Capell Blog Package';

    public function bootingPackage(): void
    {
        Blade::componentNamespace('Capell\\Blog\\View\\Components', 'capell-blog');

        foreach (config('capell-blog.livewire_components', []) as $name => $class) {
            Livewire::component($name, $class);
        }

        CapellCore::registerModel(BlogModelEnum::Article, Models\Article::class);

        Relation::morphMap([
            'article' => Models\Article::class,
        ]);

        CapellCore::addSitemapPages('archives', ArchivePageSitemap::class);

        if ($this->app->runningInConsole() && (class_exists(AboutCommand::class) && class_exists(InstalledVersions::class))) {
            AboutCommand::add('Capell', [
                self::$name => fn () => InstalledVersions::getPrettyVersion('capell-app/blog'),
            ]);
        }

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
            self::class,
            sort: 9,
            permissions: $this->getPackagePermissions(),
            demoCommand: true,
            demoParams: ['author', 'sites'],
        );

        CapellAdmin::registerResource(
            ResourceEnum::Page,
            class: Resources\ArticleResource::class,
            name: BlogResourceEnum::Article->name
        );

        CapellCore::registerComponent('Widget', 'Article', 'capell-blog::widget.page.article');

        CapellAdmin::registerSchema(SchemaEnum::Page, Schemas\Page\ArticlePageSchema::class);
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
