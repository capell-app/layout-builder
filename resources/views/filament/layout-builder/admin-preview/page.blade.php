@php
    use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
    use Capell\LayoutBuilder\Models\Widget;
    use Illuminate\Support\Str;
@endphp

<div
    class="clb-preview-page"
    data-capell-layout-builder-admin-preview="true"
>
    <main class="clb-preview-main">
        @forelse ($containers as $containerKey => $container)
            @php
                $containerHandle = $handleForContainer((string) $containerKey);
                $containerTitle = (string) ($container['meta']['name'] ?? Str::of((string) $containerKey)->headline());
                $colspan = min(12, max(1, (int) data_get($container, 'meta.colspan', 12)));
                $responsiveStyles = collect(LayoutBreakpoint::cases())
                    ->map(function (LayoutBreakpoint $breakpoint) use ($container, $colspan): string {
                        $responsiveColspan = min(12, max(1, (int) data_get($container, 'meta.responsive.' . $breakpoint->value . '.colspan', $colspan)));

                        return '--clb-preview-' . $breakpoint->value . '-colspan: ' . $responsiveColspan;
                    })
                    ->implode('; ');
                $widgets = is_array($container['widgets'] ?? null) ? $container['widgets'] : [];
            @endphp

            <section
                class="clb-preview-container"
                style="--clb-preview-colspan: {{ $colspan }}; {{ $responsiveStyles }}"
                data-clb-preview-node="{{ $containerHandle }}"
                data-clb-preview-node-type="container"
                aria-label="{{ __('capell-layout-builder::button.select_container', ['container' => $containerTitle]) }}"
            >
                <div class="clb-preview-container-label">
                    {{ $containerTitle }}
                </div>

                <div class="clb-preview-widgets">
                    @forelse ($widgets as $widgetIndex => $containerWidget)
                        @php
                            $widget = $containerWidgets[$containerKey][$widgetIndex] ?? null;
                            $widgetHandle = $handleForWidget((string) $containerKey, (int) $widgetIndex);
                        @endphp

                        <article
                            class="clb-preview-widget"
                            data-clb-preview-node="{{ $widgetHandle }}"
                            data-clb-preview-node-type="widget"
                        >
                            @if ($widget instanceof Widget)
                                {!! $renderWidgetPreview($widget, is_array($containerWidget) ? $containerWidget : [], (string) $containerKey, (int) $widgetIndex) !!}
                            @else
                                <div class="clb-preview-fallback">
                                    {{ __('capell-admin::message.unknown_widget', ['widget' => data_get($containerWidget, 'widget_key', __('capell-admin::generic.unknown'))]) }}
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="clb-preview-empty">
                            {{ __('capell-layout-builder::message.container_empty') }}
                        </div>
                    @endforelse
                </div>
            </section>
        @empty
            <div class="clb-preview-empty clb-preview-empty-page">
                {{ __('capell-layout-builder::message.layout_empty') }}
            </div>
        @endforelse
    </main>
</div>
