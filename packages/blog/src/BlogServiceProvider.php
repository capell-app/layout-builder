<?php

declare(strict_types=1);

namespace Capell\Blog;

use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Enums\SchemaTypeEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Blog\Actions\InstallBlogPackageAction;
use Capell\Blog\Enums\BlogModelEnum;
use Capell\Blog\Enums\BlogResourceEnum;
use Capell\Blog\Enums\WidgetComponentEnum;
use Capell\Blog\Enums\WidgetSchemaEnum;
use Capell\Blog\Filament\Resources\Articles\Schemas\Types\ArticlePageSchema;
use Capell\Blog\Listeners\AddBlogPagesToNavigation;
use Capell\Blog\Models\Tag;
use Capell\Blog\Services\BlogCreator;
use Capell\Blog\Services\Loader\BlogLoader;
use Capell\Blog\Services\Sitemap\ArchivePageSitemap;
use Capell\Blog\Services\Sitemap\TagPageSitemap;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Events\NavigationCreating;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Packages\AbstractPackageServiceProvider;
use Capell\Layout\Enums\ComponentTypeEnum;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Models\Content;
use Composer\InstalledVersions;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
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
        Blade::anonymousComponentNamespace('Capell\\Blog\\View\\Components');

        foreach (config('capell-blog.livewire_components', []) as $name => $class) {
            Livewire::component($name, $class);
        }

        View::composer('capell::components.footer.index', function (\Illuminate\View\View $view): void {
            $view->getFactory()->startPush('footer.components', view('capell-blog::components.footer.tags')->render());
        });

        if ($this->app->runningInConsole() && (class_exists(AboutCommand::class) && class_exists(InstalledVersions::class))) {
            AboutCommand::add('Capell', [
                self::$name => fn () => InstalledVersions::getPrettyVersion('capell-app/blog'),
            ]);
        }

        Event::listen(
            NavigationCreating::class,
            AddBlogPagesToNavigation::class,
        );

        $this->registerPublishCommands();

        CapellAdmin::serving(function (): void {
            CapellCore::addDefaultPage('blog', 'Blog', function ($site, $languages): void {
                (new BlogCreator)->createBlogPage($site, languages: $languages);
            });

            CapellCore::addDefaultPage('archives', 'Blog Archives', function ($site, $languages): void {
                $blogPage = BlogLoader::getBlogPage($site);

                $archivesPage = (new BlogCreator)->createArchivesPage($site, $blogPage, languages: $languages);

                (new BlogCreator)->createArchivePage($site, $archivesPage, languages: $languages);
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
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->startWith(function (InstallCommand $command): void {
                    $command->info('Installing Capell Blog Package...');

                    InstallBlogPackageAction::run();

                    $command->call(
                        'capell:publish-migrations',
                        [
                            '--items' => [
                                'alter_tags_table',
                            ],
                            '--path' => __DIR__ . '/../database/migrations',
                        ]
                    );

                    $command->info('Publishing Capell Blog...');
                    $command->call('vendor:publish', ['--tag' => 'capell-blog-config']);
                });
            });
    }

    public function registeringPackage(): void
    {
        parent::registeringPackage();

        $this->registerRelationships();

        CapellCore::registerPackage(
            self::$name,
            class: self::class,
            path: __DIR__,
            sort: 9,
            permissions: $this->getPackagePermissions(),
            installCommand: true,
            demoCommand: true,
            demoParams: ['author', 'sites'],
        );

        Relation::morphMap(
            collect(BlogModelEnum::cases())
                ->mapWithKeys(fn (BlogModelEnum $model): array => [Str::snake($model->name) => $model->value])
                ->all()
        );

        CapellAdmin::registerResource(
            ResourceEnum::Page,
            class: BlogResourceEnum::Article->getResource(),
            name: BlogResourceEnum::Article->value
        );

        CapellAdmin::registerResource(BlogResourceEnum::Tag->name, class: BlogResourceEnum::Tag->getResource());

        CapellCore::registerComponents(ComponentTypeEnum::Widget->value, WidgetComponentEnum::cases());

        CapellAdmin::registerSchema(SchemaTypeEnum::Page, ArticlePageSchema::class);

        CapellAdmin::registerSchemas(\Capell\Layout\Enums\SchemaTypeEnum::Widget->value, WidgetSchemaEnum::cases());

        CapellCore::registerModels(BlogModelEnum::cases());

        CapellCore::registerModelRelations(ModelEnum::Page, 'tags');
        CapellCore::registerModelRelations(LayoutModelEnum::Content, 'tags');

        CapellCore::addSitemapPages('archives', ArchivePageSitemap::class);
        CapellCore::addSitemapPages('tags', TagPageSitemap::class);
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

    private function registerPublishCommands(): self
    {
        $this->publishes([
            $this->package->basePath('/../publishes/config/') => config_path(),
        ], 'capell-blog-config');

        return $this;
    }

    private function registerRelationships(): self
    {
        Page::resolveRelationUsing(
            'tags',
            fn (Page $model): MorphToMany => $model->morphToMany(Tag::class, 'taggable', 'taggables')
        );

        Site::resolveRelationUsing(
            'tags',
            fn (Site $model): HasMany => $model->hasMany(Tag::class, 'site_id')
        );

        if (class_exists(Content::class)) {
            Content::resolveRelationUsing(
                'tags',
                fn (Content $model): MorphToMany => $model->morphToMany(Tag::class, 'taggable', 'taggables')
            );

            Tag::resolveRelationUsing(
                'contents',
                fn (Tag $model): MorphToMany => $model->morphedByMany(Content::class, 'taggable', 'taggables')
            );
        }

        return $this;
    }
}
