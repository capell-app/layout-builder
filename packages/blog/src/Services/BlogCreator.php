<?php

declare(strict_types=1);

namespace Capell\Blog\Services;

use Capell\Admin\Actions\AddPageToNavigationAction;
use Capell\Admin\Filament\Resources\Pages\Schemas\Types\ResultsPageSchema;
use Capell\Admin\Filament\Resources\Types\Schemas\Types\PageTypeSchema;
use Capell\Admin\Services\Creator\LayoutCreator;
use Capell\Admin\Services\Creator\TypeCreator;
use Capell\Blog\Enums\BlogResourceEnum;
use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Enums\WidgetComponentEnum as BlogWidgetComponentEnum;
use Capell\Blog\Filament\Resources\Articles\Schemas\Types\ArticlePageSchema;
use Capell\Blog\Filament\Resources\Widgets\Schemas\Types\ArticleWidgetSchema;
use Capell\Core\Enums\LayoutGroupEnum;
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
use Capell\Layout\Enums\WidgetComponentEnum;
use Capell\Layout\Enums\WidgetTypeEnum;
use Capell\Layout\Models\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BlogCreator
{
    public static function addPagesToNavigations(array $keys, Site $site, Collection|array $pages, Collection $languages): void
    {
        Navigation::query()
            ->whereIn('key', $keys)
            ->where(
                fn (Builder $query) => $query->whereNull('site_id')
                    ->orWhere('site_id', $site->id)
            )
            ->where(
                fn (Builder $query) => $query->whereNull('language_id')
                    ->orWhereIn('language_id', $languages->pluck('id'))
            )
            ->get()
            ->each(function (Navigation $navigation) use ($pages): void {
                foreach ($pages as $page) {
                    AddPageToNavigationAction::run($page, $navigation);
                }
            });
    }

    public static function createArchivePage(
        Site $site,
        Page $parent,
        ?Type $type = null,
        ?Layout $layout = null,
        ?Collection $languages = null
    ): Page {
        if (! $type instanceof Type) {
            $type = Type::where('key', 'archive')->pageType()->first()
                ?? self::createArchivePageType();
        }

        if (! $layout instanceof Layout) {
            $layout = Layout::firstWhere('key', 'results') ?? app(LayoutCreator::class)->resultsLayout();
        }

        if (! $languages instanceof Collection) {
            $languages = $site->languages;
        }

        $page = Page::firstOrNew([
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

    public static function createArchivePageType(): Type
    {
        return Type::firstOrCreate([
            'key' => 'archive',
            'type' => TypeEnum::Page,
        ], [
            'name' => __('capell-blog::generic.blog_archive_page'),
            'group' => TypeGroupEnum::System->value,
            'admin' => [
                'type_schema' => PageTypeSchema::getKey(),
                'schema' => ResultsPageSchema::getKey(),
                'icon' => 'heroicon-o-archive-box',
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
                'with_tags' => true,
            ],
        ]);
    }

    public static function createArchivesLayout(): Layout
    {
        return Layout::firstOrCreate(['key' => 'archives'], [
            'name' => __('capell-blog::generic.archives_page'),
            'group' => LayoutGroupEnum::System->value,
            'containers' => [
                'main' => [
                    'meta' => [
                        'colspan' => 9,
                        'container' => 'full',
                    ],
                    'widgets' => [
                        ['widget_key' => 'breadcrumbs'],
                        ['widget_key' => 'archives', 'meta' => ['hide_content' => true]],
                    ],
                ],
                'sidebar' => [
                    'meta' => [
                        'colspan' => 3,
                        'override_columns' => 1,
                        'container' => 'full',
                        'padding' => ['md'],
                        'html_class' => 'sidebar-sticky space-y-10 pt-10 pb-20',
                    ],
                    'widgets' => [
                        ['widget_key' => 'latest-articles'],
                        ['widget_key' => 'tags'],
                    ],
                ],
            ],
        ]);
    }

    public static function createArchivesListWidget(?Collection $languages = null): Widget
    {
        if (! $languages instanceof Collection) {
            $languages = Language::all();
        }

        $widget = Widget::firstOrCreate([
            'key' => 'archives',
        ], [
            'name' => __('capell-blog::generic.archive'),
            'type_id' => Type::firstWhere(['key' => WidgetTypeEnum::System, 'type' => LayoutTypeEnum::Widget])?->id,
            'meta' => [
                'component' => 'capell-blog::widget.page.archives',
                'page_group' => 'article',
                'pagination' => true,
                'with_image' => true,
                'with_date' => true,
                'with_link_text' => true,
                'with_summary' => true,
                'with_tags' => true,
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

    public static function createArchivesPage(
        Site $site,
        Page $parent,
        ?Type $type = null,
        ?Layout $layout = null,
        ?Collection $languages = null
    ): Page {
        if (! $layout instanceof Layout) {
            $layout = Layout::firstWhere('key', 'archives') ?? self::createArchivesLayout();
        }

        if (! $type instanceof Type) {
            $type = Type::where('key', 'system')->pageType()->first()
                ?? app(TypeCreator::class)::systemPageType();
        }

        if (! $languages instanceof Collection) {
            $languages = $site->languages;
        }

        $page = Page::firstOrNew([
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

    public static function createArticleLayout(): Layout
    {
        return Layout::firstOrCreate(['key' => 'article'], [
            'name' => __('capell-blog::generic.article'),
            'group' => LayoutGroupEnum::Default->value,
            'containers' => [
                ...self::heroContainer(),
                'main' => [
                    'meta' => [
                        'colspan' => 9,
                        'container' => 'full',
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
                        'html_class' => 'sidebar-sticky space-y-10 pt-10 pb-20',
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

    public static function createArticlePageType(): Type
    {
        return Type::firstOrCreate([
            'key' => 'article',
            'type' => TypeEnum::Page,
        ], [
            'name' => __('capell-blog::generic.article'),
            'group' => BlogTypeGroupEnum::Article->value,
            'admin' => [
                'icon' => 'heroicon-o-newspaper',
                'type_schema' => PageTypeSchema::getKey(),
                'schema' => ArticlePageSchema::getKey(),
                'resource' => BlogResourceEnum::Article->value,
                'with_tags' => true,
            ],
        ]);
    }

    public static function createArticleWidget(Type $type): Widget
    {
        return Widget::firstOrCreate([
            'key' => 'article',
        ], [
            'name' => __('capell-blog::generic.article'),
            'type_id' => $type->id,
            'meta' => [
                'with_date' => true,
                'with_author' => false,
                'with_tags' => true,
                'with_next_prev' => true,
            ],
        ]);
    }

    public static function createArticleWidgetType(): Type
    {
        return Type::firstOrCreate([
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

    public static function createBlogPage(
        Site $site,
        ?Type $type = null,
        ?Layout $layout = null,
        ?\Illuminate\Support\Collection $languages = null
    ): Page {
        if (! $type instanceof Type) {
            $type = self::createBlogPageType();
        }

        if (! $layout instanceof Layout) {
            $layout = app(LayoutCreator::class)->resultsLayout();
        }

        if (! $languages instanceof \Illuminate\Support\Collection) {
            $languages = $site->languages;
        }

        $page = Page::firstOrNew([
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
                'title' => __('capell-blog::generic.blog'),
                'slug' => 'blog',
                'meta' => [
                    'label' => __('capell-blog::generic.blog'),
                    'title' => '<h1>' . __('capell-blog::generic.latest_articles') . '</h1>',
                ],
            ]);

            $pageTranslation->pageUrl->update([
                'params' => ['page' => 'int'],
            ]);
        });

        return $page;
    }

    public static function createBlogPageType(): Type
    {
        return Type::firstOrCreate([
            'key' => 'blog',
            'type' => TypeEnum::Page,
        ], [
            'name' => __('capell-blog::generic.blog'),
            'group' => TypeGroupEnum::System->value,
            'admin' => [
                'type_schema' => PageTypeSchema::getKey(),
                'schema' => ResultsPageSchema::getKey(),
                'icon' => 'heroicon-o-newspaper',
                'exclude_parent',
            ],
            'meta' => [
                'component' => BlogWidgetComponentEnum::BlogPage,
                'page_group' => 'article',
                'limit' => 10,
                'pagination' => true,
                'accessible' => false,
                'listable' => false,
                'exclude_parent' => true,
                'with_image' => true,
                'with_date' => true,
                'with_summary' => true,
                'with_tags' => true,
            ],
        ]);
    }

    public static function createLatestArticlesWidget(?Collection $languages = null): Widget
    {
        if (! $languages instanceof Collection) {
            $languages = Language::all();
        }

        $widget = Widget::firstOrCreate([
            'key' => 'latest-articles',
        ], [
            'name' => __('capell-blog::generic.latest_articles'),
            'type_id' => Type::firstWhere(['key' => WidgetTypeEnum::PageResults, 'type' => LayoutTypeEnum::Widget])?->id,
            'meta' => [
                'component' => WidgetComponentEnum::LivewirePages,
                'limit' => 5,
                'page_group' => 'article',
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

    private static function heroContainer(): array
    {
        if (! CapellCore::hasPackage('capell-layout')) {
            return [];
        }

        return [
            'hero' => [
                'meta' => [
                    'colspan' => 12,
                    'container' => 'full',
                ],
                'widgets' => [
                    ['widget_key' => 'hero'],
                ],
            ],
        ];
    }
}
