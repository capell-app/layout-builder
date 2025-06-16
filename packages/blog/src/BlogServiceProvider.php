<?php

declare(strict_types=1);

namespace Capell\Blog;

use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Enums\SchemaEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Blog\Actions\CreateBlogPagesAction;
use Capell\Blog\Actions\InstallBlogAction;
use Capell\Blog\Commands\BlogDemoCommand;
use Capell\Blog\Enums\BlogModelEnum;
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
use Lorisleiva\Actions\Facades\Actions;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;

class BlogServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-blog';

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

        // TODO error does not work
        // Actions::registerCommandsForAction(CreateBlogPagesAction::class);

        CapellAdmin::serving(function (): void {
            $this->registerDefaultPages();

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
                BlogDemoCommand::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->startWith(function (): void {
                    InstallBlogAction::run();
                })
                    ->endWith(function (InstallCommand $installCommand): void {
                        $installCommand->askToStarRepoOnGitHub('capell-app/site');
                    });
            });
    }

    public function registeringPackage(): void
    {
        parent::registeringPackage();

        CapellCore::registerPackage(self::$name, self::class);
    }

    private function registerDefaultPages(): void
    {
        CapellAdmin::registerResource(ResourceEnum::Page, 'article', Resources\ArticleResource::class);

        CapellCore::registerComponent('Widget', 'Article', 'capell-blog::widget.page.article');

        CapellAdmin::registerSchema(SchemaEnum::Page, Schemas\Page\ArticleDefaultPageSchema::class);

        CapellCore::addDefaultPage('blog', 'Blog', function ($site, $languages): void {
            BlogCreator::createBlogPage($site, languages: $languages);
        });

        CapellCore::addDefaultPage('archives', 'Blog Archives', function ($site, $languages): void {
            $blogPage = BlogLoader::getBlogPage($site);

            $archivesPage = BlogCreator::createArchivesPage($site, $blogPage, languages: $languages);

            BlogCreator::createArchivePage($site, $archivesPage, languages: $languages);
        });
    }
}
