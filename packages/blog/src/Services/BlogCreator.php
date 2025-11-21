<?php

declare(strict_types=1);

namespace Capell\Blog\Services;

use Capell\Admin\Actions\AddPageToNavigationAction;
use Capell\Admin\Enums\LayoutEnum;
use Capell\Admin\Enums\PageTypeEnum;
use Capell\Admin\Filament\Resources\Pages\Schemas\Types\ResultsPageSchema;
use Capell\Admin\Filament\Resources\Types\Schemas\Types\PageTypeSchema;
use Capell\Admin\Services\Creator\LayoutCreator;
use Capell\Admin\Services\Creator\TypeCreator;
use Capell\Blog\Enums\BlogLayoutEnum;
use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Enums\PageComponentEnum;
use Capell\Blog\Enums\ResourceEnum;
use Capell\Blog\Enums\WidgetComponentEnum as BlogWidgetComponentEnum;
use Capell\Blog\Enums\WidgetSchemaEnum;
use Capell\Blog\Filament\Resources\Articles\Schemas\Types\ArticlePageSchema;
use Capell\Blog\Filament\Resources\Widgets\Schemas\Types\ArticleWidgetSchema;
use Capell\Core\Enums\LayoutGroupEnum;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Enums\TypeGroupEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Navigation;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Enums\WidgetComponentEnum;
use Capell\Layout\Enums\WidgetTypeEnum;
use Capell\Layout\Filament\Resources\Types\Schemas\Types\WidgetTypeSchema;
use Capell\Layout\Models\Widget;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BlogCreator
{
    public function createTagPageType(): Type
    {
        $typeMode = CapellCore::getModel(CoreModelEnum::Type);

        return $typeMode::firstOrCreate([
            'key' => BlogPageTypeEnum::TagPage->value,
            'type' => TypeEnum::Page,
        ], [
            'name' => __('capell-admin::generic.tag_page'),
            'group' => TypeGroupEnum::System->value,
            'admin' => [
                'type_schema' => PageTypeSchema::getKey(),
                'schema' => ResultsPageSchema::getKey(),
                'icon' => 'heroicon-' . Heroicon::OutlinedTag->value,
                'required_fields' => ['title'],
            ],
            'meta' => [
                'accessible' => false,
                'listable' => false,
                'component' => PageComponentEnum::Tag,
                'limit' => 10,
                'pagination' => true,
                'with_image' => true,
                'with_date' => true,
                'with_summary' => true,
            ],
        ]);
    }

    public function createTagPage(Site $site, ?Page $parent, Collection $languages): Page
    {
        $type = $this->createTagPageType();
        $layout = $this->getLayout(LayoutEnum::Results);

        $pageModel = CapellCore::getModel(CoreModelEnum::Page);

        $page = $pageModel::firstOrNew([
            'layout_id' => $layout->id,
            'site_id' => $site->id,
            'type_id' => $type->id,
            'parent_id' => $parent?->getKey(),
        ], [
            'name' => __('capell-admin::generic.tag_page'),
        ]);

        $page->forceFill([
            'is_published' => true,
            'is_current' => true,
        ]);

        $page->save();

        $languages->each(function (Language $language) use ($page): void {
            $pageTranslation = $page->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'slug' => '*',
                'title' => __('capell-admin::generic.tag_page_title'),
            ]);

            $pageTranslation->pageUrl->update([
                'params' => ['slug' => '', 'page' => 'int'],
            ]);
        });

        return $page;
    }

    public function createTagsPage(Site $site, Collection $languages): Page
    {
        $type = $this->getPageType(PageTypeEnum::System);

        $layout = self::createBlogPageLayout();

        $pageModel = CapellCore::getModel(CoreModelEnum::Page);

        $page = $pageModel::firstOrNew([
            'layout_id' => $layout->id,
            'site_id' => $site->id,
            'type_id' => $type->id,
        ], [
            'name' => __('capell-admin::generic.tag_pages'),
        ]);

        $page->forceFill([
            'is_published' => true,
            'is_current' => true,
        ]);

        $page->save();

        $languages->each(function (Language $language) use ($page): void {
            $page->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'slug' => 'tags',
                'title' => __('capell-admin::generic.tags_page'),
            ]);
        });

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
        Site $site,
        Page $parent,
        ?Type $type = null,
        ?Layout $layout = null,
        ?Collection $languages = null,
    ): Page {
        if (! $type instanceof Type) {
            $type = Type::query()->where('key', 'archive')->pageType()->first()
                ?? self::createArchivePageType();
        }

        if (! $layout instanceof Layout) {
            $layout = Layout::query()->firstWhere('key', 'results') ?? app(LayoutCreator::class)->resultsLayout();
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
            'name' => __('capell-blog::generic.blog_archive_page'),
            'is_published' => true,
            'is_current' => true,
        ]);

        $page->save();

        $languages->each(function (Language $language) use ($page): void {
            $pageTranslation = $page->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'slug' => '*',
                'title' => __('capell-blog::generic.blog_archive_title'),
                'content' => 'Blog posts from the archive',
                'meta' => [
                    'description' => __('capell-blog::generic.archive'),
                ],
            ]);

            $pageTranslation->pageUrl->update([
                'params' => ['slug' => '', 'page' => 'int'],
            ]);
        });

        return $page;
    }

    public function createArchivePageType(): Type
    {
        return Type::query()->firstOrCreate([
            'key' => 'archive',
            'type' => TypeEnum::Page,
        ], [
            'name' => __('capell-blog::generic.blog_archive_page'),
            'group' => TypeGroupEnum::System->value,
            'admin' => [
                'type_schema' => PageTypeSchema::getKey(),
                'schema' => ResultsPageSchema::getKey(),
                'icon' => 'heroicon-o-archive-box',
                'required_fields' => ['title'],
            ],
            'meta' => [
                'accessible' => false,
                'listable' => false,
                'hidden_from_selection' => true,
                'component' => 'capell-blog::livewire.page.archive',
                'limit' => 10,
                'pagination' => true,
                'with_image' => true,
                'with_date' => true,
                'with_summary' => true,
            ],
        ]);
    }

    public function createArchivesLayout(): Layout
    {
        return Layout::query()->firstOrCreate(['key' => BlogLayoutEnum::Archives->value], [
            'name' => __('capell-blog::generic.archives_page'),
            'group' => LayoutGroupEnum::System->value,
            'containers' => [
                'main' => [
                    'meta' => [
                        'colspan' => 9,
                    ],
                    'widgets' => [
                        ['widget_key' => 'breadcrumbs'],
                        ['widget_key' => 'archives', 'meta' => ['show_page_content' => true]],
                    ],
                ],
                'sidebar' => [
                    'meta' => [
                        'colspan' => 3,
                        'override_columns' => 1,
                        'container' => 'full',
                        'padding' => ['md'],
                        'html_class' => 'sidebar-sticky space-y-10',
                    ],
                    'widgets' => [
                        ['widget_key' => 'latest-articles'],
                        ['widget_key' => 'tags'],
                    ],
                ],
            ],
        ]);
    }

    public function createBlogPageLayout(): Layout
    {
        return Layout::query()->firstOrCreate(['key' => BlogLayoutEnum::BlogPage->value], [
            'name' => __('capell-blog::generic.blog_page'),
            'group' => LayoutGroupEnum::System->value,
            'containers' => [
                'main' => [
                    'meta' => [
                        'colspan' => 9,
                    ],
                    'widgets' => [
                        ['widget_key' => 'page-slot'],
                    ],
                ],
                'sidebar' => [
                    'meta' => [
                        'colspan' => 3,
                        'override_columns' => 1,
                        'container' => 'full',
                        'padding' => ['md'],
                        'html_class' => 'sidebar-sticky space-y-10',
                    ],
                    'widgets' => [
                        ['widget_key' => 'tags'],
                        ['widget_key' => 'archives'],
                    ],
                ],
            ],
        ]);
    }

    public function createTagsLayout(): Layout
    {
        return Layout::query()->firstOrCreate(['key' => BlogLayoutEnum::Tags->value], [
            'name' => __('capell-admin::generic.tags_page'),
            'group' => LayoutGroupEnum::System->value,
            'containers' => [
                'main' => [
                    'meta' => [
                        'colspan' => 9,
                    ],
                    'widgets' => [
                        ['widget_key' => 'tags', 'meta' => ['show_page_title' => true]],
                    ],
                ],
                'sidebar' => [
                    'meta' => [
                        'colspan' => 3,
                        'override_columns' => 1,
                        'container' => 'full',
                        'padding' => ['md'],
                        'html_class' => 'sidebar-sticky space-y-10',
                    ],
                    'widgets' => [
                        ['widget_key' => 'latest-pages'],
                    ],
                ],
            ],
        ]);
    }

    public function createArchivesListWidget(?Collection $languages = null): Widget
    {
        if (! $languages instanceof Collection) {
            $languages = Language::all();
        }

        $widget = Widget::query()->firstOrCreate([
            'key' => 'archives',
        ], [
            'name' => __('capell-blog::generic.archive'),
            'type_id' => Type::query()->firstWhere(['key' => WidgetTypeEnum::System, 'type' => LayoutTypeEnum::Widget])?->id,
            'meta' => [
                'component' => 'capell-blog::widget.page.archives',
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
            ]);
        });

        return $widget;
    }

    public function createTagsWidget(Collection $languages): void
    {
        $widgetModel = CapellCore::getModel(ModelEnum::Widget);
        $typeModel = CapellCore::getModel(CoreModelEnum::Type);

        $type = $typeModel::firstWhere(['key' => WidgetTypeEnum::System, 'type' => LayoutTypeEnum::Widget]);

        $widget = $widgetModel::firstOrCreate([
            'key' => 'tags',
        ], [
            'name' => __('capell-admin::generic.tags'),
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::Tags,
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
                'content' => 'Browse by tag to explore related topics and content.',
            ]);
        });
    }

    public function createArchivesPage(
        Site $site,
        Page $parent,
        ?Type $type = null,
        ?Layout $layout = null,
        ?Collection $languages = null,
    ): Page {
        if (! $layout instanceof Layout) {
            $layout = Layout::query()->firstWhere('key', 'archives') ?? self::createArchivesLayout();
        }

        if (! $type instanceof Type) {
            $type = Type::query()->where('key', 'system')->pageType()->first()
                ?? app(TypeCreator::class)::systemPageType();
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
            'is_published' => true,
            'is_current' => true,
        ]);

        $page->save();

        $archivesText = __('capell-blog::generic.blog_archives_title');

        $languages->each(function (Language $language) use ($page, $archivesText): void {
            $page->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'slug' => str(__('capell-blog::generic.archives'))->slug(),
                'title' => __('capell-blog::generic.archives'),
                'content' => sprintf('<p>%s</p>', $archivesText),
                'meta' => [
                    'title' => __('capell-blog::generic.blog_archives_title'),
                    'description' => __('capell-blog::generic.archives'),
                ],
            ]);
        });

        return $page;
    }

    public function createArticleLayout(): Layout
    {
        return Layout::query()->firstOrCreate(['key' => 'article'], [
            'name' => __('capell-blog::generic.article'),
            'group' => LayoutGroupEnum::Default->value,
            'containers' => [
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
                        'html_class' => 'sidebar-sticky space-y-10',
                    ],
                    'widgets' => [
                        ['widget_key' => 'related-pages'],
                        ['widget_key' => 'tags'],
                        ['widget_key' => 'archives'],
                    ],
                ],
            ],
        ]);
    }

    public function createArticlePageType(): Type
    {
        return Type::query()->firstOrCreate([
            'key' => 'article',
            'type' => TypeEnum::Page,
        ], [
            'name' => __('capell-blog::generic.article'),
            'group' => BlogTypeGroupEnum::Article->value,
            'admin' => [
                'icon' => 'heroicon-o-newspaper',
                'type_schema' => PageTypeSchema::getKey(),
                'schema' => ArticlePageSchema::getKey(),
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
                'with_author' => false,
                'with_next_prev' => true,
            ],
        ]);
    }

    public function relatedPagesWidget(Type $type, \Illuminate\Support\Collection $languages): void
    {
        $widget = Widget::query()->firstOrCreate([
            'key' => 'related-pages',
        ], [
            'name' => __('capell-admin::generic.related_pages'),
            'type_id' => $type->id,
            'meta' => [
                'component' => BlogWidgetComponentEnum::PageRelated,
                'limit' => 6,
                'pagination' => false,
                'exclude_types' => ['home'],
                'exclude_parent' => true,
                'with_summary' => true,
                'with_link_text' => true,
                'with_image' => true,
                'columns' => 1,
            ],
            'admin' => [
                'icon' => 'heroicon-c-link',
                'type_schema' => WidgetTypeSchema::getKey(),
                'schema' => WidgetSchemaEnum::Related->value,
            ],
        ]);

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-layout::heading.related_pages'),
            ]);
        });
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
                'type_schema' => PageTypeSchema::getKey(),
                'schema' => ArticleWidgetSchema::getKey(),
                'icon' => 'heroicon-o-newspaper',
            ],
            'meta' => [
                'component' => 'capell-blog::widget.page.article',
                'margin' => ['xl'],
            ],
        ]);
    }

    public function createBlogPage(
        Site $site,
        ?Type $type = null,
        ?Layout $layout = null,
        ?\Illuminate\Support\Collection $languages = null,
    ): Page {
        if (! $type instanceof Type) {
            $type = self::createBlogPageType();
        }

        if (! $layout instanceof Layout) {
            $layout = self::createBlogPageLayout();
        }

        if (! $languages instanceof \Illuminate\Support\Collection) {
            $languages = $site->languages;
        }

        $page = Page::query()->firstOrNew([
            'layout_id' => $layout->id,
            'site_id' => $site->id,
            'type_id' => $type->id,
        ]);

        $page->forceFill([
            'name' => __('capell-blog::generic.blog'),
            'is_published' => true,
            'is_current' => true,
        ]);

        $page->save();

        $languages->each(function (Language $language) use ($page): void {
            $pageTranslation = $page->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-blog::generic.latest_articles'),
                'slug' => 'blog',
                'meta' => [
                    'label' => __('capell-blog::generic.blog'),
                ],
            ]);

            $pageTranslation->pageUrl->update([
                'params' => ['page' => 'int'],
            ]);
        });

        return $page;
    }

    public function createBlogPageType(): Type
    {
        return Type::query()->firstOrCreate([
            'key' => 'blog',
            'type' => TypeEnum::Page,
        ], [
            'name' => __('capell-blog::generic.blog'),
            'group' => TypeGroupEnum::System->value,
            'admin' => [
                'type_schema' => PageTypeSchema::getKey(),
                'schema' => ResultsPageSchema::getKey(),
                'icon' => 'heroicon-o-newspaper',
                'exclude_parent' => true,
                'required_fields' => ['title'],
            ],
            'meta' => [
                'component' => BlogWidgetComponentEnum::BlogPage,
                'page_group' => strtolower(ResourceEnum::Article->name),
                'limit' => 10,
                'pagination' => true,
                'listable' => false,
                'sitemap' => true,
                'exclude_parent' => true,
                'with_image' => true,
                'with_date' => true,
                'with_summary' => true,
            ],
        ]);
    }

    public function createLatestArticlesWidget(?Collection $languages = null): Widget
    {
        if (! $languages instanceof Collection) {
            $languages = Language::all();
        }

        $widget = Widget::query()->firstOrCreate([
            'key' => 'latest-articles',
        ], [
            'name' => __('capell-blog::generic.latest_articles'),
            'type_id' => Type::query()->firstWhere(['key' => WidgetTypeEnum::PageResults, 'type' => LayoutTypeEnum::Widget])?->id,
            'meta' => [
                'component' => WidgetComponentEnum::LivewirePages,
                'limit' => 5,
                'page_group' => strtolower(ResourceEnum::Article->name),
                'pagination' => false,
                'with_date' => true,
                'with_image' => true,
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
        $typeModel = CapellCore::getModel(CoreModelEnum::Type);

        $type = $typeModel::where('key', $key)->pageType()->first();

        if ($type) {
            return $type;
        }

        if ($key instanceof PageTypeEnum) {
            $key = $key->value;
        }

        return app(TypeCreator::class)->createPageType($key);
    }

    private function getLayout(LayoutEnum|string $key): Layout
    {
        if ($key instanceof LayoutEnum) {
            $key = $key->value;
        }

        $layoutModel = CapellCore::getModel(CoreModelEnum::Layout);

        $layout = $layoutModel::firstWhere('key', $key);

        if ($layout) {
            return $layout;
        }

        return app(LayoutCreator::class)->create($key);
    }
}
