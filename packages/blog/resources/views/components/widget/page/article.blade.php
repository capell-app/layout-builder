@php
    use Capell\Blog\View\Components\ArticleMeta;
    use Capell\Frontend\Enums\RenderHookLocation;
    use Capell\Frontend\Facades\Frontend;
    use Capell\Frontend\Support\Render\RenderHookRegistry;

    $page = Frontend::page();
    $theme = Frontend::theme();
@endphp

@props([
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
    'headingSize' => $widget->getMeta('heading_size', 'h1'),
    'withAuthor' => (bool) $widget->getMeta('with_author'),
    'withDate' => (bool) $widget->getMeta('with_date'),
    'withNextPrev' => (bool) $widget->getMeta('with_next_prev'),
])
@php
    $author ??= $withAuthor ? $page->loadMissing('creator')->creator : null;
    $nextPage ??= null;
    $previousPage ??= null;
    $articleMeta = app(RenderHookRegistry::class)->renderAll(
        RenderHookLocation::ArticleMeta,
        [
            'withAuthor' => $withAuthor,
            'author' => $author,
        ],
    );

    $articleMetaComponent = resolve(ArticleMeta::class, [
        'withAuthor' => $withAuthor,
        'author' => $author,
    ]);

    if ($articleMeta === '') {
        $articleMeta = view('capell-blog::components.article-meta', [
            'tagPage' => $articleMetaComponent->tagPage,
            'tags' => $articleMetaComponent->tags,
            'author' => $author,
            'withAuthor' => $withAuthor,
        ])->render();
    }
@endphp

<x-capell-layout-builder::widget.wrapper
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
    container-class="flex flex-col gap-6"
>
    <div class="grid">
        <x-capell::content
            :$containerKey
            :image="$page->image"
            :heading-size="$headingSize"
            :content="$page->translation->content"
            :content-type="$page->type->content_structure"
            :muted="in_array($containerKey, $theme->secondary_containers)"
            :text-align="$widget->getMeta('align')"
            :title="$page->translation->title"
            :heading-style="$widget->getMeta('heading_style')"
        >
            @if ($withDate)
                <x-capell-blog::page.published-date
                    class="mt-4 whitespace-nowrap"
                    :date="$page->visible_from ?: $page->created_at"
                />
            @endif
        </x-capell::content>
    </div>

    @if ($articleMeta !== '')
        {!! $articleMeta !!}
    @else
        <div
            class="article-meta mb-4 mt-10 flex items-end justify-between border-t border-black/10 pt-8 dark:border-gray-700/50"
        >
            @if ($withAuthor && $author)
                <x-capell-blog::page.author :$author />
            @endif

            @if ($articleMetaComponent->tags->isNotEmpty())
                <div
                    class="article-tags flex flex-col items-center gap-x-10 gap-y-6 md:flex-row md:justify-between lg:flex-row-reverse"
                >
                    <x-capell-blog::page.tags
                        :tagPage="$articleMetaComponent->tagPage"
                        :tags="$articleMetaComponent->tags"
                        with_tag_icon="true"
                    />
                </div>
            @endif
        </div>
    @endif

    @if ($withNextPrev && ($previousPage || $nextPage))
        <div
            class="neighbor-links mt-10 flex divide-y divide-gray-100 border-t border-gray-100 pt-6 md:divide-x md:divide-y-0"
        >
            @if ($previousPage)
                <x-capell::page.neighbor-link
                    :neighbor-page="$previousPage"
                    neighbor="previous"
                />
            @endif

            @if ($nextPage)
                <x-capell::page.neighbor-link
                    :neighbor-page="$nextPage"
                    neighbor="next"
                    class="ml-auto"
                />
            @endif
        </div>
    @endif
</x-capell-layout-builder::widget.wrapper>
