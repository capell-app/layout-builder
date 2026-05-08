@props([
    'align' => $widget->getMeta('align'),
    'title' => $widget->translation?->title,
    'content' => $widget->translation?->content,
    'container',
    'loop',
    'containerKey',
    'containerWidth' => null,
    'widget',
])

<x-capell-layout-builder::widget.wrapper
    class="widget-snippet"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <x-capell::content
        class="widget-content"
        :compact="true"
        :content="$content"
        :content-type="$widget->type->content_structure"
        :divider="$widget->getMeta('content_divider')"
        :heading-size="$widget->getMeta('heading_size', 'h3')"
        :title="$title"
        :text-align="$align"
    />
</x-capell-layout-builder::widget.wrapper>
