<?php

declare(strict_types=1);

namespace Capell\Blog\Providers;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\ResourceEnum as AdminResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Blog\Enums\LivewirePageComponentEnum;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Listeners\ArticleTranslationSavedListener;
use Capell\Blog\Models\Article;
use Capell\Blog\Support\BlogModelRegistrar;
use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Data\PageTypeData;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\LayoutBuilder\Models\Section;
use Capell\PublishingStudio\WorkspaceRegistry;
use Capell\Tags\Models\Tag;
use Composer\InstalledVersions;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;

class BlogServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-blog';

    public static string $packageName = 'capell-app/blog';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasViews(self::$name)
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        $this->app->register(AdminServiceProvider::class);

        $this
            ->registerPackageMetadata();

        $this->app->booted(function (): void {
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
            ->registerRelationships()
            ->registerModels()
            ->registerModelRelations()
            ->registerAdminResources()
            ->registerAboutCommand()
            ->registerPackageAssets()
            ->registerBlazeComponents()
            ->registerBladeComponents()
            ->registerLivewireComponents()
            ->registerTypes()
            ->registerTranslationEvents()
            ->registerPublishingStudio();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            permissions: $this->getPackagePermissions(),
            description: fn (): string => __('capell-blog::package.description'),
        );

        return $this;
    }

    private function registerPackageAssets(): self
    {
        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', static::$packageName),
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

    private function registerAdminResources(): self
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

        return $this;
    }

    private function registerModelRelations(): self
    {
        CapellCore::registerModelRelations(Page::class, 'tags');
        CapellCore::registerModelRelations(Section::class, 'tags');

        Tag::resolveRelationUsing(
            'articles',
            fn (Tag $tag): MorphToMany => $tag->morphedByMany(Article::class, 'taggable', 'taggables'),
        );

        return $this;
    }

    private function registerBladeComponents(): self
    {
        Blade::componentNamespace('Capell\\Blog\\View\\Components', 'capell-blog');
        Blade::anonymousComponentNamespace('Capell\\Blog\\View\\Components');

        return $this;
    }

    private function registerBlazeComponents(): self
    {
        RegisterBlazeOptimizedViewsAction::run(__DIR__ . '/../../resources/views/components');

        return $this;
    }

    private function registerLivewireComponents(): self
    {
        if ($this->isLivewireV3()) {
            foreach (LivewirePageComponentEnum::getComponents() as $name => $component) {
                if (! $component) {
                    continue;
                }

                Livewire::component($name, $component);
            }
        } else {
            Livewire::addNamespace(
                namespace: 'capell-blog',
                classNamespace: 'Capell\\Blog\\Livewire',
                classPath: __DIR__ . '/../Livewire',
                classViewPath: __DIR__ . '/../../resources/views/livewire',
            );
        }

        return $this;
    }

    private function isLivewireV3(): bool
    {
        $version = InstalledVersions::getVersion('livewire/livewire');

        return version_compare($version, '4.0.0', '<');
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

    private function registerRelationships(): self
    {
        Page::resolveRelationUsing(
            'tags',
            fn (Page $model): MorphToMany => $model->morphToMany(
                Tag::class,
                'taggable',
                'taggables',
            ),
        );

        Site::resolveRelationUsing(
            'tags',
            fn (Site $model): HasMany => $model->hasMany(Tag::class, 'site_id'),
        );

        if (class_exists(Section::class)) {
            Section::resolveRelationUsing(
                'tags',
                fn (Section $model): MorphToMany => $model->morphToMany(Tag::class, 'taggable', 'taggables'),
            );

            Tag::resolveRelationUsing(
                'sections',
                fn (Tag $model): MorphToMany => $model->morphedByMany(Section::class, 'taggable', 'taggables'),
            );
        }

        return $this;
    }

    private function registerTranslationEvents(): self
    {
        Event::listen('eloquent.saved: ' . Translation::class, ArticleTranslationSavedListener::class);

        return $this;
    }

    private function registerTypes(): self
    {
        CapellCore::registerPageType(
            new PageTypeData(
                name: 'article',
                model: Article::class,
                label: fn (): string => __('capell-blog::generic.article'),
            ),
        );

        return $this;
    }

    private function registerPublishingStudio(): self
    {
        WorkspaceRegistry::register(Article::class);

        return $this;
    }
}
