@props([
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

<x-capell-layout-builder::widget.wrapper
    class="widget-campaign-lead-form"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section
        class="campaign-lead-form px-6 py-12"
        data-campaign-goal="{{ $widget->getMeta('goal_key') }}"
        data-campaign-location="lead-form"
    >
        @if ($widget->translation?->title)
            <h2 class="mb-4 text-3xl font-bold">
                {{ $widget->translation->title }}
            </h2>
        @endif

        @if ($widget->translation?->content)
            <div class="mb-6">{!! $widget->translation->content !!}</div>
        @endif

        @if ($widget->getMeta('form_handle'))
            <livewire:capell-form-builder.form
                :handle="$widget->getMeta('form_handle')"
            />
        @endif
    </section>
</x-capell-layout-builder::widget.wrapper>
