@php
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
                                @include('capell-layout-builder::filament.layout-builder.admin-preview.container', ['entry' => $entry])
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
                            @include('capell-layout-builder::filament.layout-builder.admin-preview.container', ['entry' => $entry])
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif
    </main>
</div>
