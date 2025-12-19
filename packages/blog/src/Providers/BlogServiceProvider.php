<?php

declare(strict_types=1);

namespace Capell\Blog\Providers;

use Capell\Admin\Enums\ResourceEnum as AdminResourceEnum;
use Capell\Admin\Enums\SchemaTypeEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Blog\BlogModelRegistrar;
use Capell\Blog\Commands\CreateBlogPagesCommand;
use Capell\Blog\Commands\DemoCommand;
use Capell\Blog\Commands\InstallCommand;
use Capell\Blog\Enums\PageComponentEnum;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Enums\WidgetComponentEnum;
use Capell\Blog\Enums\WidgetSchemaEnum;
use Capell\Blog\Filament\Resources\Articles\Schemas\Types\ArticlePageSchema;
use Capell\Blog\Listeners\AddBlogPagesToNavigation;
use Capell\Blog\Models\Tag;
use Capell\Blog\Services\Creator\BlogCreator;
use Capell\Blog\Services\Loader\BlogLoader;
use Capell\Blog\Services\Sitemap\ArchivePageSitemap;
use Capell\Blog\Services\Sitemap\TagPageSitemap;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Events\NavigationCreating;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Packages\AbstractPackageServiceProvider;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Frontend\Services\RenderHookRegistry;
use Capell\Layout\Enums\ComponentTypeEnum;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Enums\SchemaTypeEnum as LayoutSchemaEnum;
use Capell\Layout\Models\Content;
use Composer\InstalledVersions;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;

class BlogServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-blog';

    public static string $packageName = 'capell-app/blog';

    public static string $description = 'Article page type with blog archives.';

    public function bootingPackage(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->registerAll();
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasCommands([
                CreateBlogPagesCommand::class,
                DemoCommand::class,
                InstallCommand::class,
            ]);
    }

    public function registeringPackage(): void
    {
        parent::registeringPackage();

        $this
            ->registerResources()
            ->registerModels()
            ->registerRelationships()
            ->registerPackageMetadata();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::getPackage(static::$packageName)->isInstalled();
    }

    private function registerAll(): self
    {
        return $this
            ->registerModelRelations()
            ->registerPublishCommands()
            ->registerViewComposers()
            ->registerAboutCommand()
            ->registerNavigationListener()
            ->registerWidgetComponents()
            ->registerSchemas()
            ->registerSitemapPages()
            ->registerDefaultPages()
            ->registerBladeComponents()
            ->registerLivewireComponents()
            ->registerRenderHooks();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            path: __DIR__,
            sort: 9,
            description: static::getDescription(),
            permissions: $this->getPackagePermissions(),
            installCommand: 'capell-blog:install',
            demoCommand: 'capell-blog:demo', // unchanged signature now handled by DemoCommand
            demoParams: ['user', 'sites'],
            requirements: [
                AdminServiceProvider::$packageName,
                FrontendServiceProvider::$packageName,
            ],
            version: $this->getVersion(),
            url: 'https://capell.app',
        );

        return $this;
    }

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'dev';
        }

        if (! InstalledVersions::isInstalled(static::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(static::$packageName) ?? 'dev';
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

    private function registerModels(): self
    {
        BlogModelRegistrar::register();

        return $this;
    }

    private function registerModelRelations(): self
    {
        CapellCore::registerModelRelations(CoreModelEnum::Page, 'tags');
        CapellCore::registerModelRelations(ModelEnum::Content, 'tags');

        return $this;
    }

    private function registerBladeComponents(): self
    {
        foreach (WidgetComponentEnum::getComponents() as $name => $component) {
            if (! $component) {
                continue;
            }
            Blade::component($name, $component);
        }

        Blade::componentNamespace('Capell\\Blog\\View\\Components', 'capell-blog');
        Blade::anonymousComponentNamespace('Capell\\Blog\\View\\Components');

        return $this;
    }

    private function registerLivewireComponents(): self
    {
        foreach (PageComponentEnum::getComponents() as $name => $component) {
            if (! $component) {
                continue;
            }
            Livewire::component($name, $component);
        }

        return $this;
    }

    private function registerViewComposers(): self
    {
        View::composer('capell::components.footer.index', function (\Illuminate\View\View $view): void {
            $view->getFactory()->startPush(
                'footer.components',
                // @phpstan-ignore-next-line
                view('capell-blog::components.footer.tags')->render(),
            );
        });

        return $this;
    }

    private function registerRenderHooks(): self
    {
        app(RenderHookRegistry::class)->register(
            RenderHookLocation::ArticleMeta,
            fn ($context) => view('capell-blog::hooks.article-meta', [
                'withAuthor' => $context->withAuthor ?? false,
                'author' => $context->author ?? null,
                'tags' => $context->tags ?? null,
                'tagPage' => $context->tagPage ?? null,
            ])->render(),
        );

        app(RenderHookRegistry::class)->register(
            RenderHookLocation::BeforeContent,
            function ($context): ?string {
                $tags = $context->item->tags ?? null;
                if (! $tags || $tags->isEmpty()) {
                    return null;
                }

                return view('capell-blog::page.tags', [
                    'item' => $context->item ?? null,
                    'tags' => $tags,
                ])->render();
            },
        );

        app(RenderHookRegistry::class)->register(
            RenderHookLocation::AfterTitle,
            function ($context): ?string {
                if (
                    (! ($context->publishDate ?? null) || ($context->publishDatePosition ?? null) !== 'bottom')
                    && (empty($context->tags) || $context->tags->isEmpty())
                ) {
                    return null;
                }

                return view('capell-blog::hooks.asset-after-title', [
                    'publishDate' => $context->publishDate ?? null,
                    'publishDatePosition' => $context->publishDatePosition ?? null,
                    'tags' => $context->tags ?? null,
                    'publishDateOutput' => $context->publishDateOutput ?? null,
                ])->render();
            },
        );

        return $this;
    }

    private function registerAboutCommand(): self
    {
        if ($this->app->runningInConsole() && (class_exists(AboutCommand::class) && class_exists(InstalledVersions::class))) {
            AboutCommand::add('Capell', [
                self::$name => fn () => InstalledVersions::getPrettyVersion('capell-app/blog'),
            ]);
        }

        return $this;
    }

    private function registerNavigationListener(): self
    {
        Event::listen(NavigationCreating::class, AddBlogPagesToNavigation::class);

        return $this;
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
            fn (Page $model): MorphToMany => $model->morphToMany(
                Tag::class,
                'taggable',
                'taggables',
            )
                ->ordered(),
        );

        Site::resolveRelationUsing(
            'tags',
            fn (Site $model): HasMany => $model->hasMany(Tag::class, 'site_id'),
        );

        if (class_exists(Content::class)) {
            Content::resolveRelationUsing(
                'tags',
                fn (Content $model): MorphToMany => $model->morphToMany(Tag::class, 'taggable', 'taggables'),
            );

            Tag::resolveRelationUsing(
                'contents',
                fn (Tag $model): MorphToMany => $model->morphedByMany(Content::class, 'taggable', 'taggables'),
            );
        }

        return $this;
    }

    private function registerResources(): self
    {
        CapellAdmin::registerResource(
            AdminResourceEnum::Page,
            class: ResourceEnum::Article->value,
            name: strtolower(ResourceEnum::Article->name),
        );

        CapellAdmin::registerResource(ResourceEnum::Tag->name, class: ResourceEnum::Tag->value);

        return $this;
    }

    private function registerWidgetComponents(): self
    {
        CapellCore::registerComponents(ComponentTypeEnum::Widget->name, WidgetComponentEnum::cases());

        return $this;
    }

    private function registerSchemas(): self
    {
        CapellAdmin::registerSchema(SchemaTypeEnum::Page, ArticlePageSchema::class);

        foreach (WidgetSchemaEnum::cases() as $schemas) {
            CapellAdmin::registerSchema(LayoutSchemaEnum::Widget, $schemas->value);
        }

        return $this;
    }

    private function registerSitemapPages(): self
    {
        CapellCore::addSitemapPages('archives', ArchivePageSitemap::class);
        CapellCore::addSitemapPages('tags', TagPageSitemap::class);

        return $this;
    }

    private function registerDefaultPages(): self
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

        return $this;
    }
}
