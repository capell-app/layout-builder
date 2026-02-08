<?php

declare(strict_types=1);

namespace Capell\Blog\Providers;

use Capell\Admin\Enums\ResourceEnum as AdminResourceEnum;
use Capell\Admin\Enums\SchemaTypeEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Blog\Console\Commands\CreateBlogPagesCommand;
use Capell\Blog\Console\Commands\DemoCommand;
use Capell\Blog\Console\Commands\InstallCommand;
use Capell\Blog\Console\Commands\SetupCommand;
use Capell\Blog\Enums\PageComponentEnum;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Enums\WidgetComponentEnum;
use Capell\Blog\Enums\WidgetSchemaEnum;
use Capell\Blog\Filament\Resources\Articles\Schemas\Types\ArticlePageSchema;
use Capell\Blog\Listeners\AddBlogPagesToNavigation;
use Capell\Blog\Models\Tag;
use Capell\Blog\Support\BlogModelRegistrar;
use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Blog\Support\Sitemap\ArchivesSitemap;
use Capell\Blog\Support\Sitemap\ArticlesSitemap;
use Capell\Blog\Support\Sitemap\TagsSitemap;
use Capell\Blog\Support\StaticSite\BlogStaticSiteExtension;
use Capell\Blog\View\Components\ArticleMeta;
use Capell\Blog\View\Components\AssetAfterTitle;
use Capell\Blog\View\Components\Footer\Pages;
use Capell\Blog\View\Components\Footer\Tags;
use Capell\Blog\View\Components\Page\BeforeContentTags;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Events\NavigationCreating;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\StaticSite\StaticSiteExtensionRegistry;
use Capell\Frontend\Data\RenderHookContext;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Layout\Enums\ComponentTypeEnum;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Enums\TypeSchemaEnum as LayoutSchemaEnum;
use Capell\Layout\Models\Content;
use Composer\InstalledVersions;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;

class BlogServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-blog';

    public static string $packageName = 'capell-app/blog';

    public static string $description = 'Article page type with blog archives.';

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
                SetupCommand::class,
            ]);
    }

    public function registeringPackage(): void
    {
        $this
            ->registerResources()
            ->registerModels()
            ->registerRelationships()
            ->registerPackageMetadata();

        $this->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this->bootInstalledPackage();
        });
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::getPackage(static::$packageName)->isInstalled();
    }

    private function bootInstalledPackage(): self
    {
        return $this
            ->registerModelRelations()
            ->registerPublishCommands()
            ->registerAboutCommand()
            ->registerNavigationListener()
            ->registerWidgetComponents()
            ->registerSchemas()
            ->registerSitemapPages()
            ->registerDefaultPages()
            ->registerBladeComponents()
            ->registerLivewireComponents()
            ->registerRenderHooks()
            ->registerStaticSiteExtensions();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            sort: 9,
            description: static::getDescription(),
            permissions: $this->getPackagePermissions(),
            installCommand: 'capell-blog:install',
            setupCommand: 'capell-blog:setup',
            demoCommand: 'capell-blog:demo',
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

    private function registerRenderHooks(): self
    {
        resolve(RenderHookRegistry::class)->register(
            RenderHookLocation::Footer,
            fn (RenderHookContext $context): ?View => resolve(Tags::class, [
                'item' => $context->item,
            ])
                ?->render() ?: null,
            target: 'footer.index',
        );

        resolve(RenderHookRegistry::class)->register(
            RenderHookLocation::Footer,
            fn (RenderHookContext $context): ?View => resolve(Pages::class, [
                'item' => $context->item,
            ])
                ?->render() ?: null,
            target: 'footer.index',
        );

        resolve(RenderHookRegistry::class)->register(
            RenderHookLocation::ArticleMeta,
            fn (RenderHookContext $context): ?View => resolve(ArticleMeta::class, [
                'withAuthor' => $context->withAuthor ?? false,
                'author' => $context->author ?? null,
            ])
                ?->render() ?: null,
        );

        resolve(RenderHookRegistry::class)->register(
            RenderHookLocation::BeforeContent,
            fn (RenderHookContext $context): ?View => resolve(BeforeContentTags::class, [
                'item' => $context->item ?? null,
                'tags' => $context->item->tags ?? null,
            ])
                ?->render() ?: null,
        );

        resolve(RenderHookRegistry::class)->register(
            RenderHookLocation::AfterTitle,
            fn (RenderHookContext $context): ?View => resolve(AssetAfterTitle::class, [
                'publishDate' => $context->publishDate ?? null,
                'publishDatePosition' => $context->publishDatePosition ?? null,
                'tags' => $context->tags ?? null,
                'publishDateOutput' => $context->publishDateOutput ?? null,
            ])
                ?->render() ?: null,
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
        CapellCore::addSitemapPages('archives', ArchivesSitemap::class);
        CapellCore::addSitemapPages('articles', ArticlesSitemap::class);
        CapellCore::addSitemapPages('tags', TagsSitemap::class);

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

    private function registerStaticSiteExtensions(): self
    {
        $registry = resolve(StaticSiteExtensionRegistry::class);

        if (! $registry->has('blog-tags-archives')) {
            $registry->register('blog-tags-archives', resolve(BlogStaticSiteExtension::class));
        }

        return $this;
    }
}
