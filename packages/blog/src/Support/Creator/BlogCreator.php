<?php

declare(strict_types=1);

namespace Capell\Blog\Support\Creator;

use Capell\Admin\Filament\Configurators\Pages\ResultsPageConfigurator;
use Capell\Admin\Filament\Configurators\Types\PageTypeConfigurator;
use Capell\Admin\LayoutBuilder\Filament\Configurators\Types\WidgetTypeConfigurator;
use Capell\Blog\Actions\EnsureArticlePublishingDefaultsAction;
use Capell\Blog\Actions\EnsureBlogPublishingSurfaceAction;
use Capell\Blog\Enums\BlogLayoutEnum;
use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Enums\LivewirePageComponentEnum;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Enums\WidgetComponentEnum as BlogWidgetComponentEnum;
use Capell\Blog\Enums\WidgetConfiguratorEnum;
use Capell\Blog\Filament\Configurators\Articles\ArticlePageConfigurator;
use Capell\Blog\Filament\Configurators\Widgets\ArticleWidgetConfigurator;
use Capell\Blog\Models\Article;
use Capell\Core\Actions\SetupPageUrlsAction;
use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\LayoutGroupEnum;
use Capell\Core\Enums\PageTypeEnum;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Enums\TypeGroupEnum;
use Capell\Core\Enums\UrlParamTypeEnum;
use Capell\Core\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\Core\LayoutBuilder\Enums\LivewireComponentsEnum;
use Capell\Core\LayoutBuilder\Support\Creator\TypeCreator as LayoutTypeCreator;
use Capell\Core\LayoutBuilder\Support\Creator\WidgetCreator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Models\Widget;
use Capell\Core\Support\Creator\LayoutCreator;
use Capell\Core\Support\Creator\TypeCreator;
use Capell\Navigation\Actions\AddPageToNavigationAction;
use Capell\Navigation\Models\Navigation;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class BlogCreator
{
    public function setup(Site $site, bool $createWidgets = true): void
    {
        EnsureArticlePublishingDefaultsAction::run($createWidgets);
        EnsureBlogPublishingSurfaceAction::run($site, $site->getAllLanguages(), $createWidgets);
    }

    public function createTagPageType(): Type
    {
        /** @var class-string<Type> $typeMode */
        $typeMode = Type::class;

        return $typeMode::query()->firstOrCreate([
            'key' => BlogPageTypeEnum::Tag->value,
            'type' => TypeEnum::Page,
        ], [
            'name' => __('capell-blog::generic.tag_page'),
            'group' => TypeGroupEnum::System->value,
            'admin' => [
                'type_configurator' => PageTypeConfigurator::getKey(),
                'configurator' => ResultsPageConfigurator::getKey(),
                'icon' => 'heroicon-' . Heroicon::OutlinedTag->value,
                'required_fields' => ['title'],
            ],
            'meta' => [
                'accessible' => false,
                'component' => LivewirePageComponentEnum::TagPage,
                'livewire' => true,
                'limit' => 10,
                'listable' => false,
                'pagination' => true,
                'url_params' => ['tag' => UrlParamTypeEnum::String->value],
                'with_date' => true,
                'with_image' => true,
                'with_summary' => true,
            ],
        ]);
    }

    public function createTagPage(Site $site, ?Page $parent = null, ?Collection $languages = null, ?Type $type = null, ?Layout $layout = null): Page
    {
        $site->unsetRelation('siteDomains');
        $site->loadMissing(['language', 'siteDomains.language']);

        $type ??= $this->createTagPageType();
        $layout ??= $this->getResultsLayout();
        $languages ??= $site->getAllLanguages();
        $parent ??= $this->createTagsPage($site, $this->createBlogPage($site));

        $pageModel = Page::class;

        $page = $pageModel::query()->firstOrNew([
            'layout_id' => $layout->id,
            'site_id' => $site->id,
            'type_id' => $type->id,
            'parent_id' => $parent?->getKey(),
        ], [
            'name' => __('capell-blog::generic.tag_page'),
        ]);

        $page->save();

        $languages->each(function (Language $language) use ($page): void {
            $translation = $page->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-blog::generic.tag_page_title'),
                'meta' => ['slug' => '*'],
            ]);
        });

        SetupPageUrlsAction::run($page);

        return $page;
    }

    public function createTagsPage(Site $site, ?Page $parent, ?Collection $languages = null, ?Type $type = null, ?Layout $layout = null, bool $createWidgets = false): Page
    {
        $site->unsetRelation('siteDomains');
        $site->loadMissing(['language', 'siteDomains.language']);

        $type ??= $this->getPageType(PageTypeEnum::System);
        $layout ??= self::createTagsLayout();
        $languages ??= $site->getAllLanguages();

        if ($createWidgets) {
            $this->createTagsWidget($languages);
            $resultsWidgetType = resolve(LayoutTypeCreator::class)->resultsWidgetType();
            resolve(WidgetCreator::class)->latestPagesWidget($resultsWidgetType, $languages);
        }

        $pageModel = Page::class;

        $page = $pageModel::query()->firstOrNew([
            'layout_id' => $layout->id,
            'site_id' => $site->id,
            'type_id' => $type->id,
            'parent_id' => $parent?->getKey(),
        ], [
            'name' => __('capell-blog::generic.tags_page'),
        ]);

        $page->save();

        $languages->each(function (Language $language) use ($page): void {
            $page->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-blog::generic.tags_page_title'),
                'content' => '<p>' . __('capell-blog::generic.tags_page_description') . '</p>',
                'meta' => [
                    'label' => __('capell-blog::generic.tags'),
                    'slug' => 'tags',
                ],
            ]);
        });

        SetupPageUrlsAction::run($page);

        return $page;
    }

    public function addPagesToNavigations(array $keys, Site $site, Collection|array $pages, Collection $languages): void
    {
        Navigation::query()
            ->whereIn('key', $keys)
            ->where(
                fn (Builder $query) => $query->whereNull('site_id')
                    ->orWhere('site_id', $site->id),
            )
            ->where(
                fn (Builder $query) => $query->whereNull('language_id')
                    ->orWhereIn('language_id', $languages->pluck('id')),
            )
            ->get()
            ->each(function (Navigation $navigation) use ($pages): void {
                foreach ($pages as $page) {
                    AddPageToNavigationAction::run($page, $navigation);
                }
            });
    }

    public function createArchivePage(
        Page $parent,
        ?Type $type = null,
        ?Layout $layout = null,
        ?Collection $languages = null,
    ): Page {
        $site = $parent->site;

        if (! $type instanceof Type) {
            $type = Type::query()->where('key', BlogPageTypeEnum::Archive)->pageType()->first()
                ?? self::createArchivePageType();
        }

        if (! $layout instanceof Layout) {
            $layout = Layout::query()->firstWhere('key', 'results') ?? resolve(LayoutCreator::class)->create(LayoutEnum::Results);
        }

        if (! $languages instanceof Collection) {
            $languages = $site->getAllLanguages();
        }

        $page = Page::query()->firstOrNew([
            'layout_id' => $layout->id,
            'site_id' => $site->id,
            'type_id' => $type->id,
            'parent_id' => $parent->id,
        ]);

        $page->forceFill([
            'name' => __('capell-blog::generic.blog_archive_page'),
        ]);

        $page->save();

        $languages->each(function (Language $language) use ($page): void {
            $page->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-blog::generic.blog_archive_title'),
                'meta' => [
                    'description' => __('capell-blog::generic.archive'),
                    'slug' => '*',
                ],
            ]);
        });

        SetupPageUrlsAction::run($page);

        return $page;
    }

    public function createArchivePageType(): Type
    {
        return Type::query()->firstOrCreate([
            'key' => BlogPageTypeEnum::Archive->value,
            'type' => TypeEnum::Page,
        ], [
            'name' => __('capell-blog::generic.blog_archive_page'),
            'group' => TypeGroupEnum::System->value,
            'admin' => [
                'type_configurator' => PageTypeConfigurator::getKey(),
                'configurator' => ResultsPageConfigurator::getKey(),
                'icon' => 'heroicon-o-archive-box',
                'required_fields' => ['title'],
            ],
            'meta' => [
                'accessible' => false,
                'component' => LivewirePageComponentEnum::ArchivePage,
                'livewire' => true,
                'hidden_from_selection' => true,
                'limit' => 10,
                'listable' => false,
                'pagination' => true,
                'url_params' => ['date' => UrlParamTypeEnum::String->value],
                'with_date' => true,
                'with_image' => true,
                'with_summary' => true,
            ],
        ]);
    }

    public function createArchivesLayout(): Layout
    {
        $containers = [
            'main' => [
                'meta' => [
                    'colspan' => 9,
                ],
                'widgets' => [
                    ['widget_key' => 'breadcrumbs'],
                    ['widget_key' => 'archives', 'meta' => ['show_page_content' => true, 'show_page_title' => true]],
                ],
            ],
            'sidebar' => [
                'meta' => [
                    'colspan' => 3,
                    'override_columns' => 1,
                    'container' => 'full',
                    'padding' => ['md'],
                    'html_class' => 'sidebar-sticky space-y-8',
                ],
                'widgets' => [
                    ['widget_key' => 'latest-articles', 'meta' => ['hide_no_results' => true]],
                    ['widget_key' => 'tags', 'meta' => ['hide_no_results' => true]],
                ],
            ],
        ];

        return Layout::query()->firstOrCreate(['key' => BlogLayoutEnum::Archives->value], [
            'name' => __('capell-blog::generic.archives'),
            'group' => LayoutGroupEnum::System->value,
            'containers' => $containers,
            'widgets' => $this->widgetKeys($containers),
        ]);
    }

    public function createBlogPageLayout(): Layout
    {
        $containers = [
            'main' => [
                'meta' => [
                    'colspan' => 9,
                ],
                'widgets' => [
                    ['widget_key' => 'breadcrumbs'],
                    ['widget_key' => 'page-content'],
                    ['widget_key' => 'page-slot'],
                ],
            ],
            'sidebar' => [
                'meta' => [
                    'colspan' => 3,
                    'override_columns' => 1,
                    'container' => 'full',
                    'padding' => ['md'],
                    'html_class' => 'sidebar-sticky space-y-8',
                ],
                'widgets' => [
                    ['widget_key' => 'tags', 'meta' => ['hide_no_results' => true]],
                    ['widget_key' => 'archives', 'meta' => ['hide_no_results' => true]],
                ],
            ],
        ];

        return Layout::query()->firstOrCreate(['key' => BlogLayoutEnum::BlogPage->value], [
            'name' => __('capell-blog::generic.blog_page'),
            'group' => LayoutGroupEnum::System->value,
            'containers' => $containers,
            'widgets' => $this->widgetKeys($containers),
        ]);
    }

    public function createTagsLayout(): Layout
    {
        $containers = [
            'main' => [
                'meta' => [
                    'colspan' => 9,
                ],
                'widgets' => [
                    ['widget_key' => 'breadcrumbs'],
                    ['widget_key' => 'tags', 'meta' => ['show_page_title' => true, 'show_page_content' => true]],
                ],
            ],
            'sidebar' => [
                'meta' => [
                    'colspan' => 3,
                    'override_columns' => 1,
                    'container' => 'full',
                    'padding' => ['md'],
                    'html_class' => 'sidebar-sticky space-y-8',
                ],
                'widgets' => [
                    ['widget_key' => 'latest-pages', 'meta' => ['hide_no_results' => true]],
                ],
            ],
        ];

        return Layout::query()->firstOrCreate(['key' => BlogLayoutEnum::Tags->value], [
            'name' => __('capell-blog::generic.tags'),
            'group' => LayoutGroupEnum::System->value,
            'containers' => $containers,
            'widgets' => $this->widgetKeys($containers),
        ]);
    }

    public function createArchivesWidget(?Collection $languages = null): Widget
    {
        if (! $languages instanceof Collection) {
            $languages = Language::all();
        }

        $typeCreator = resolve(LayoutTypeCreator::class);
        $type = $typeCreator->resultsWidgetType();

        $widget = Widget::query()->firstOrCreate([
            'key' => 'archives',
        ], [
            'name' => __('capell-blog::generic.article_archives'),
            'type_id' => $type->id,
            'meta' => [
                'component' => BlogWidgetComponentEnum::Archives,
                'page_group' => strtolower(ResourceEnum::Article->name),
                'pagination' => true,
                'with_image' => true,
                'with_date' => true,
                'with_link_text' => true,
                'with_summary' => true,
                'margin' => ['b-lg'],
            ],
        ]);

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-blog::generic.archives'),
                'meta' => [
                    'no_results' => __('capell-blog::messages.no_archives_found'),
                ],
            ]);
        });

        return $widget;
    }

    public function createTagsWidget(Collection $languages): void
    {
        $widgetModel = Widget::class;

        $typeCreator = resolve(LayoutTypeCreator::class);
        $type = $typeCreator->resultsWidgetType();

        $widget = $widgetModel::query()->firstOrCreate([
            'key' => 'tags',
        ], [
            'name' => __('capell-blog::generic.tags'),
            'type_id' => $type->id,
            'meta' => [
                'component' => BlogWidgetComponentEnum::Tags,
                'page_model' => Relation::getMorphAlias(Article::class),
                'size' => 'sm',
            ],
            'admin' => [
                'icon' => 'heroicon-' . Heroicon::OutlinedTag->value,
            ],
        ]);

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-blog::generic.tags'),
                'meta' => [
                    'no_results' => __('capell-blog::messages.no_tags_found'),
                ],
            ]);
        });
    }

    public function createArchivesPage(
        Page $parent,
        ?Type $type = null,
        ?Layout $layout = null,
        ?Collection $languages = null,
    ): Page {
        $site = $parent->site;
        if (! $layout instanceof Layout) {
            $layout = Layout::query()->firstWhere('key', 'archives') ?? self::createArchivesLayout();
        }

        if (! $type instanceof Type) {
            $type = Type::query()->where('key', 'system')->pageType()->first()
                ?? resolve(TypeCreator::class)->systemPageType();
        }

        if (! $languages instanceof Collection) {
            $languages = $site->languages;
        }

        $page = Page::query()->firstOrNew([
            'layout_id' => $layout->id,
            'site_id' => $site->id,
            'type_id' => $type->id,
            'parent_id' => $parent->id,
        ]);

        $page->forceFill([
            'name' => __('capell-blog::generic.blog_archives_page'),
        ]);

        $page->save();

        $languages->each(function (Language $language) use ($page): void {
            $page->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-blog::generic.archives'),
                'content' => sprintf('<p>%s</p>', __('capell-blog::generic.blog_archives_description')),
                'meta' => [
                    'title' => __('capell-blog::generic.blog_archives_title'),
                    'description' => __('capell-blog::generic.archives'),
                    'slug' => str(__('capell-blog::generic.archives'))->slug(),
                ],
            ]);
        });

        SetupPageUrlsAction::run($page);

        return $page;
    }

    public function createArticleLayout(bool $createWidgets = true): Layout
    {
        if ($createWidgets) {
            $languages = Language::all();
            $widgetCreator = resolve(WidgetCreator::class);
            $typeCreator = resolve(LayoutTypeCreator::class);
            $systemWidgetType = $typeCreator->systemWidgetType();
            $pageContentWidgetType = $typeCreator->pageContentWidgetType();
            $resultsType = $typeCreator->resultsWidgetType();

            $widgetCreator->breadcrumbWidget($systemWidgetType);
            $widgetCreator->pageSlotWidget($systemWidgetType);
            $widgetCreator->pageContentWidget($pageContentWidgetType);

            $articleType = $this->createArticleWidgetType();
            $this->createArticleWidget($articleType);

            $this->relatedArticlesWidget($resultsType, $languages);
            $this->createTagsWidget($languages);
            $this->createArchivesWidget($languages);
        }

        $containers = [
            'main' => [
                'meta' => [
                    'colspan' => 9,
                ],
                'widgets' => [
                    ['widget_key' => 'breadcrumbs'],
                    ['widget_key' => 'article'],
                ],
            ],
            'sidebar' => [
                'meta' => [
                    'colspan' => 3,
                    'override_columns' => 1,
                    'container' => 'full',
                    'padding' => ['md'],
                    'html_class' => 'sidebar-sticky space-y-8',
                ],
                'widgets' => [
                    ['widget_key' => 'latest-articles', 'meta' => ['hide_no_results' => true]],
                    ['widget_key' => 'tags', 'meta' => ['hide_no_results' => true]],
                    ['widget_key' => 'archives', 'meta' => ['hide_no_results' => true]],
                ],
            ],
        ];

        return Layout::query()->firstOrCreate(['key' => BlogLayoutEnum::Article->value], [
            'name' => __('capell-blog::generic.article'),
            'group' => LayoutGroupEnum::Default->value,
            'containers' => $containers,
            'widgets' => $this->widgetKeys($containers),
        ]);
    }

    public function createArticlePageType(): Type
    {
        return Type::query()->firstOrCreate([
            'key' => BlogPageTypeEnum::Article->value,
            'type' => TypeEnum::Page,
        ], [
            'name' => __('capell-blog::generic.article'),
            'group' => BlogTypeGroupEnum::Article->value,
            'admin' => [
                'icon' => 'heroicon-o-newspaper',
                'type_configurator' => PageTypeConfigurator::getKey(),
                'configurator' => ArticlePageConfigurator::getKey(),
                'resource' => strtolower(ResourceEnum::Article->name),
                'required_fields' => ['title'],
            ],
        ]);
    }

    public function createArticleWidget(Type $type): Widget
    {
        return Widget::query()->firstOrCreate([
            'key' => 'article',
        ], [
            'name' => __('capell-blog::generic.article'),
            'type_id' => $type->id,
            'meta' => [
                'with_date' => true,
                'with_author' => true,
                'with_next_prev' => true,
            ],
        ]);
    }

    public function relatedArticlesWidget(?Type $type = null, ?Collection $languages = null): Widget
    {
        if (! $type instanceof Type) {
            $typeCreator = resolve(LayoutTypeCreator::class);
            $type = $typeCreator->resultsWidgetType();
        }

        if (! $languages instanceof Collection) {
            $languages = Language::all();
        }

        $widget = Widget::query()->firstOrCreate([
            'key' => 'related-pages',
        ], [
            'name' => __('capell-admin::generic.related_pages'),
            'type_id' => $type->id,
            'meta' => [
                'component' => BlogWidgetComponentEnum::PageRelated,
                'limit' => 6,
                'pagination' => false,
                'page_model' => Relation::getMorphAlias(Article::class),
                'exclude_types' => ['home'],
                'exclude_parent' => true,
                'with_summary' => true,
                'with_link_text' => true,
                'with_image' => true,
                'columns' => 1,
            ],
            'admin' => [
                'icon' => 'heroicon-c-link',
                'type_configurator' => WidgetTypeConfigurator::getKey(),
                'configurator' => WidgetConfiguratorEnum::Related->name,
            ],
        ]);

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-layout-builder::heading.related_pages'),
            ]);
        });

        return $widget;
    }

    public function createArticleWidgetType(): Type
    {
        return Type::query()->firstOrCreate([
            'key' => 'article',
            'type' => LayoutTypeEnum::Widget,
        ], [
            'name' => __('capell-blog::generic.article'),
            'group' => TypeGroupEnum::System->value,
            'admin' => [
                'type_configurator' => PageTypeConfigurator::getKey(),
                'configurator' => ArticleWidgetConfigurator::getKey(),
                'icon' => 'heroicon-o-newspaper',
            ],
            'meta' => [
                'component' => BlogWidgetComponentEnum::Article,
                'margin' => ['xl'],
            ],
        ]);
    }

    public function createBlogPage(
        Site $site,
        ?Type $type = null,
        ?Layout $layout = null,
        ?Collection $languages = null,
        array $meta = [],
    ): Page {
        $site->unsetRelation('siteDomains');
        $site->loadMissing(['language', 'siteDomains.language']);

        if (! $type instanceof Type) {
            $type = self::createBlogPageType();
        }

        if (! $layout instanceof Layout) {
            $layout = self::createBlogPageLayout();
        }

        if (! $languages instanceof Collection) {
            $languages = $site->languages;
        }

        $page = Page::query()->firstOrNew([
            'layout_id' => $layout->id,
            'site_id' => $site->id,
            'type_id' => $type->id,
        ]);

        $page->mergeMeta($meta);

        $page->forceFill([
            'name' => __('capell-blog::generic.blog'),
        ]);

        $page->save();

        $languages->each(function (Language $language) use ($page): void {
            $page->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-blog::generic.latest_articles'),
                'meta' => [
                    'label' => __('capell-blog::generic.blog'),
                    'no_results' => __('capell-blog::messages.no_articles_found'),
                    'slug' => 'blog',
                ],
            ]);
        });

        SetupPageUrlsAction::run($page);

        return $page;
    }

    public function createBlogPageType(): Type
    {
        return Type::query()->firstOrCreate([
            'key' => BlogPageTypeEnum::Blog->value,
            'type' => TypeEnum::Page,
        ], [
            'name' => __('capell-blog::generic.blog'),
            'group' => TypeGroupEnum::Results->value,
            'admin' => [
                'type_configurator' => PageTypeConfigurator::getKey(),
                'configurator' => ResultsPageConfigurator::getKey(),
                'icon' => 'heroicon-o-newspaper',
                'exclude_parent' => true,
                'required_fields' => ['title'],
            ],
            'meta' => [
                'component' => LivewirePageComponentEnum::BlogPage,
                'livewire' => true,
                'exclude_parent' => true,
                'limit' => 10,
                'listable' => false,
                'page_group' => strtolower(ResourceEnum::Article->name),
                'pagination' => true,
                'sitemap' => true,
                'url_params' => ['page' => UrlParamTypeEnum::Int->value],
                'with_date' => true,
                'with_image' => true,
                'with_summary' => true,
            ],
        ]);
    }

    public function createLatestArticlesWidget(?Collection $languages = null): Widget
    {
        if (! $languages instanceof Collection) {
            $languages = Language::all();
        }

        $typeCreator = resolve(LayoutTypeCreator::class);
        $type = $typeCreator->resultsWidgetType();

        $widget = Widget::query()->firstOrCreate([
            'key' => 'latest-articles',
        ], [
            'name' => __('capell-blog::generic.latest_articles'),
            'type_id' => $type->id,
            'meta' => [
                'component' => LivewireComponentsEnum::PagesWidget,
                'livewire' => true,
                'limit' => 5,
                'page_model' => Relation::getMorphAlias(Article::class),
                'page_group' => strtolower(ResourceEnum::Article->name),
                'pagination' => false,
                'with_date' => true,
                'with_image' => true,
                'with_summary' => true,
                'with_link_text' => true,
                'margin' => ['b-lg'],
            ],
            'admin' => [
                'icon' => 'heroicon-o-newspaper',
            ],
        ]);

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-blog::generic.latest_articles'),
            ]);
        });

        return $widget;
    }

    private function getPageType(string|PageTypeEnum $key): Type
    {
        $typeModel = Type::class;

        $type = $typeModel::query()->where('key', $key)->pageType()->first();

        if ($type !== null) {
            return $type;
        }

        if ($key instanceof PageTypeEnum) {
            $key = $key->value;
        }

        return resolve(TypeCreator::class)->createPageType($key);
    }

    private function getLayout(LayoutEnum|string $key): Layout
    {
        if ($key instanceof LayoutEnum) {
            $key = $key->value;
        }

        $layoutModel = Layout::class;

        $layout = $layoutModel::query()->firstWhere('key', $key);

        if ($layout !== null) {
            return $layout;
        }

        return resolve(LayoutCreator::class)->create($key);
    }

    private function getResultsLayout(): Layout
    {
        $layout = $this->getLayout(LayoutEnum::Results);
        $containers = $layout->getAttribute('containers');

        if (is_array($containers) && $containers !== []) {
            return $layout;
        }

        $widgetCreator = resolve(WidgetCreator::class);
        $widgetCreator->breadcrumbWidget();
        $widgetCreator->pageContentWidget();
        $widgetCreator->pageSlotWidget();
        $widgetCreator->latestPagesWidget();

        $containers = [
            'main' => [
                'meta' => [
                    'colspan' => 9,
                ],
                'widgets' => [
                    ['widget_key' => 'breadcrumbs'],
                    ['widget_key' => 'page-content'],
                    ['widget_key' => 'page-slot'],
                ],
            ],
            'sidebar' => [
                'meta' => [
                    'colspan' => 3,
                    'override_columns' => 1,
                    'container' => ContainerWidthEnum::Full,
                    'padding' => ['md'],
                    'html_class' => 'sidebar-sticky space-y-8',
                ],
                'widgets' => [
                    ['widget_key' => 'latest-pages'],
                ],
            ],
        ];

        $layout->update([
            'containers' => $containers,
            'widgets' => $this->widgetKeys($containers),
        ]);

        return $layout;
    }

    private function widgetKeys(array $containers): array
    {
        return collect($containers)
            ->flatMap(fn (array $container): array => $container['widgets'] ?? [])
            ->unique('widget_key')
            ->pluck('widget_key')
            ->values()
            ->all();
    }
}
