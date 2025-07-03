<?php

declare(strict_types=1);

namespace Capell\Blog\Services;

use Capell\Admin\Actions\AddPageToNavigationAction;
use Capell\Admin\Enums\ContentEditorEnum;
use Capell\Admin\Filament\Schemas\Page\ResultsPageSchema;
use Capell\Admin\Filament\Schemas\Type\PageTypeSchema;
use Capell\Admin\Services\Creator\LayoutCreator;
use Capell\Admin\Services\Creator\PageTypeCreator;
use Capell\Blog\Enums\BlogResourceEnum;
use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Enums\WidgetComponentEnum as BlogWidgetComponentEnum;
use Capell\Blog\Filament\Schemas\Page\ArticlePageSchema;
use Capell\Blog\Filament\Schemas\Widget\ArticleWidgetSchema;
use Capell\Core\Enums\LayoutGroupEnum;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Enums\TypeGroupEnum;
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
    public static function addPagesToNavigations(array $handles, Site $site, Collection|array $pages, Collection $languages): void
    {
        Navigation::query()
            ->whereIn('handle', $handles)
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
            'parent_uuid' => $parent->uuid,
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
                'schema' => PageTypeSchema::getKey(),
                'default_schema' => ResultsPageSchema::getKey(),
                'icon' => 'heroicon-o-archive-box',
            ],
            'meta' => [
                'hidden' => true,
                'accessible' => false,
                'component' => 'capell-blog::livewire.page.archive',
                'limit' => 10,
                'pagination' => true,
                'with_image' => true,
                'with_published' => true,
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
                'with_published' => true,
                'with_link_text' => true,
                'with_summary' => true,
                'with_tags' => true,
                'margin' => ['b-lg'],
            ],
            'admin' => [
                'notes' => 'Displays a list of archived pages grouped by year and month',
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
                ?? app(PageTypeCreator::class)::systemPageType();
        }

        if (! $languages instanceof Collection) {
            $languages = $site->languages;
        }

        $page = Page::firstOrNew([
            'layout_id' => $layout->id,
            'site_id' => $site->id,
            'type_id' => $type->id,
            'parent_uuid' => $parent->uuid,
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
            'key' => 'article',
            'name' => __('capell-blog::generic.article'),
            'group' => LayoutGroupEnum::Default->value,
            'containers' => [
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
                'accessible' => false,
                'content_editor' => ContentEditorEnum::RichEditor->value,
                'icon' => 'heroicon-o-newspaper',
                'schema' => PageTypeSchema::getKey(),
                'default_schema' => ArticlePageSchema::getKey(),
                'resource' => BlogResourceEnum::Article->name,
                'with_tags' => true,
                'exclude' => true,
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
                'with_published' => true,
                'with_author' => false,
                'with_tags' => true,
                'with_next_prev' => true,
            ],
            'admin' => [
                'notes' => 'Article content, including title, author, and tags, with navigation to next and previous articles',
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
                'schema' => PageTypeSchema::getKey(),
                'default_schema' => ArticleWidgetSchema::getKey(),
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
                    'title' => '<h1>'.__('capell-blog::generic.latest_articles').'</h1>',
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
                'schema' => PageTypeSchema::getKey(),
                'default_schema' => ResultsPageSchema::getKey(),
                'icon' => 'heroicon-o-newspaper',
                'exclude_parent',
            ],
            'meta' => [
                'component' => BlogWidgetComponentEnum::BlogPage,
                'page_group' => 'article',
                'limit' => 10,
                'pagination' => true,
                'exclude_parent' => true,
                'with_image' => true,
                'with_published' => true,
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
            'name' => __('capell-blog::generic.blog'),
            'type_id' => Type::firstWhere(['key' => WidgetTypeEnum::PageResults, 'type' => LayoutTypeEnum::Widget])?->id,
            'meta' => [
                'component' => WidgetComponentEnum::LivewirePages,
                'limit' => 5,
                'page_group' => 'article',
                'pagination' => false,
                'with_published' => true,
                'with_summary' => true,
                'with_link_text' => true,
                'margin' => ['b-lg'],
            ],
            'admin' => [
                'icon' => 'heroicon-o-newspaper',
                'notes' => 'Displays a list of the latest articles',
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
}
