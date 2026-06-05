@php
    use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
    use Capell\LayoutBuilder\Models\Widget;
    use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
    use Illuminate\Support\Str;

    $position = 0;
    $containerEntries = collect($containers)
        ->map(function (array $container, int|string $containerKey) use (&$position): array {
            $area = $container['meta']['area'] ?? null;
            $normalizedArea = is_string($area) && trim($area) !== ''
                ? Str::of($area)->slug()->lower()->toString()
                : LayoutAreaRegistry::MAIN;

            return [
                'key' => (string) $containerKey,
                'container' => $container,
                'area' => $normalizedArea,
                'areaLabel' => Str::of($normalizedArea)->headline()->toString(),
                'position' => $position++,
            ];
        })
        ->values();

    $mainEntries = $containerEntries
        ->filter(fn (array $entry): bool => $entry['area'] === LayoutAreaRegistry::MAIN)
        ->values();
    $sidebarEntries = $containerEntries
        ->filter(fn (array $entry): bool => in_array($entry['area'], ['aside', 'sidebar'], true))
        ->values();
    $otherAreaGroups = $containerEntries
        ->reject(fn (array $entry): bool => $entry['area'] === LayoutAreaRegistry::MAIN || in_array($entry['area'], ['aside', 'sidebar'], true))
        ->groupBy('area');

    $contentRegions = collect([
        [
            'kind' => 'main',
            'area' => LayoutAreaRegistry::MAIN,
            'label' => __('capell-layout-builder::generic.main_content_area'),
            'entries' => $mainEntries,
        ],
    ]);

    if ($sidebarEntries->isNotEmpty()) {
        $contentRegions->push([
            'kind' => 'sidebar',
            'area' => 'sidebar',
            'label' => __('capell-layout-builder::generic.sidebar_area'),
            'entries' => $sidebarEntries,
        ]);
    }

    $otherRegions = $otherAreaGroups
        ->map(fn (mixed $entries, string $area): array => [
            'kind' => 'area',
            'area' => $area,
            'label' => Str::of($area)->headline()->toString(),
            'entries' => $entries->values(),
        ])
        ->values();
@endphp

<div
    class="clb-preview-page"
    data-capell-layout-builder-admin-preview="true"
>
    <main class="clb-preview-main">
        @if ($containerEntries->isEmpty())
            <div class="clb-preview-empty clb-preview-empty-page">
                {{ __('capell-layout-builder::message.layout_empty') }}
            </div>
        @else
            <div
                @class([
                    'clb-preview-content-layout',
                    'clb-preview-content-layout-with-sidebar' => $sidebarEntries->isNotEmpty(),
                ])
            >
                @foreach ($contentRegions as $region)
                    <section
                        class="clb-preview-region clb-preview-region-{{ $region['kind'] }}"
                        data-clb-preview-area="{{ $region['area'] }}"
                    >
                        <div class="clb-preview-region-label">
                            {{ $region['label'] }}
                        </div>

                        <div
                            class="clb-preview-container-list"
                            data-clb-preview-container-list
                        >
                            @foreach ($region['entries'] as $entry)
                                @php
                                    $containerKey = $entry['key'];
                                    $container = $entry['container'];
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
                                    data-clb-preview-container-position="{{ $entry['position'] }}"
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
                                                    <div
                                                        class="clb-preview-fallback"
                                                    >
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
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            @foreach ($otherRegions as $region)
                <section
                    class="clb-preview-region clb-preview-region-area"
                    data-clb-preview-area="{{ $region['area'] }}"
                >
                    <div class="clb-preview-region-label">
                        {{ $region['label'] }}
                    </div>

                    <div
                        class="clb-preview-container-list"
                        data-clb-preview-container-list
                    >
                        @foreach ($region['entries'] as $entry)
                            @php
                                $containerKey = $entry['key'];
                                $container = $entry['container'];
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
                                data-clb-preview-container-position="{{ $entry['position'] }}"
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
                                                <div
                                                    class="clb-preview-fallback"
                                                >
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
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif
    </main>
</div>
