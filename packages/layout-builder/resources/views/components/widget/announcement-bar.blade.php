@props([
    'align' => $widget->getMeta('align', 'center'),
    'title' => $widget->translation?->title,
    'content' => $widget->translation?->content,
    'container',
    'loop',
    'containerKey',
    'containerWidth' => null,
    'widget',
])

<x-capell-layout-builder::widget.wrapper
    class="widget-announcement-bar"
    container-class="text-center"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <div class="border-current/15 rounded-md border px-4 py-3">
        <x-capell::content
            class="widget-content"
            :compact="true"
            :content="$content"
            :content-type="$widget->type->content_structure"
            :heading-size="$widget->getMeta('heading_size', 'h3')"
            :title="$title"
            :text-align="$align"
        />

        @if ($widget->getMeta('actions'))
            <x-capell-layout-builder::actions
                class="mt-3"
                :actions="$widget->getMeta('actions')"
                :align="$align"
            />
        @endif
    </div>
</x-capell-layout-builder::widget.wrapper>
