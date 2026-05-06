@php
    use Capell\Core\Enums\ContainerWidthEnum;
    use Capell\Core\Enums\MediaConversionEnum;
    use Capell\Frontend\Actions\GetLayoutContainerWidthAction;
    use Capell\LayoutBuilder\Enums\ContainerAlignmentEnum;
    use Capell\LayoutBuilder\Enums\ResponsiveVisibilityEnum;
    use Capell\LayoutBuilder\Facades\CapellLayout;
    use Spatie\MediaLibrary\MediaCollections\Models\Media;
@endphp

@props([
    'colspan' => 12,
    'columnStart' => 0,
    'container',
    'containerKey',
    'containerIndex',
    'containerWidth' => ! empty($container['meta']['container'])
    ? ContainerWidthEnum::from($container['meta']['container'])
    : GetLayoutContainerWidthAction::run(),
    'layout',
    'spacing' => $container['meta']['spacing'] ?? null,
    'padding' => $container['meta']['padding'] ?? [],
    'margin' => $container['meta']['margin'] ?? [],
    'htmlClass' => '',
    'previousColspan' => null,
    'pageSlot' => null,
    'tag' => $container['meta']['tag'] ?? 'div',
])
{{-- format-ignore-start --}}
@php
    if (! empty($container['meta']['html_class'])) {
        $htmlClass .= ' ' . $container['meta']['html_class'];
    }

    $alignment = ContainerAlignmentEnum::tryFrom((string) ($container['meta']['alignment'] ?? ''))
        ?? ContainerAlignmentEnum::Stretch;

    $hiddenOn = (array) ($container['meta']['hidden_on'] ?? []);
    $hideOnMobile = in_array(ResponsiveVisibilityEnum::Mobile->value, $hiddenOn, true);
    $hideOnTablet = in_array(ResponsiveVisibilityEnum::Tablet->value, $hiddenOn, true);
    $hideOnDesktop = in_array(ResponsiveVisibilityEnum::Desktop->value, $hiddenOn, true);

    /** @var ?Media $backgroundImage */
    $backgroundImage = $layout->getFirstMedia($containerKey . '-background');

    $currentColspan = $colspan;
@endphp
{{-- format-ignore-end --}}
@if ($colspan === 12 && $previousColspan && $previousColspan !== 12)
    {{-- format-ignore-start --}}</div>
</div>{{-- format-ignore-end --}}
@endif

{{-- format-ignore-start --}}
@if ($backgroundImage)
    <div class="relative">
        <div
            @if ($backgroundImage)
                style="{{ $backgroundImage ? 'background-image:url('.$backgroundImage->getAvailableUrl([MediaConversionEnum::Large->value]).');' : '' }}"
            @endif
            @class([
                "absolute top-0 bottom-0 left-0 w-1/2 -z-1 h-full bg-cover bg-center bg-no-repeat",
            ])
        >
        </div>
        @endif

        @if ($colspan !== 12)
            @if (! $previousColspan || $previousColspan === 12)
                <div
                    @class([
                        $containerWidth->getContainerClass(),
                    ])
                >
                    <div class="flex w-full flex-col gap-x-12 lg:grid lg:grid-cols-12 xl:gap-x-16">
                        @endif

                        <div
                            @class([
                                "lg:col-span-[var(--colspan)]",
                                "lg:col-start-[var(--column-start)]",
                            ])
                            style="--colspan: {{ $colspan }}; --column-start: {{ $columnStart }};"
                        >
                            @endif
                            {{-- format-ignore-end --}}

<div
    id="layout-container-{{ $containerKey }}"
    @class([
        'layout-container',
        $htmlClass => (bool) $htmlClass,
        'self-start justify-self-start' => $alignment === ContainerAlignmentEnum::Start,
        'self-center justify-self-center' => $alignment === ContainerAlignmentEnum::Center,
        'self-end justify-self-end' => $alignment === ContainerAlignmentEnum::End,
        'w-full self-stretch justify-self-stretch' => $alignment === ContainerAlignmentEnum::Stretch,
        'hidden' => $hideOnMobile && $hideOnTablet && $hideOnDesktop,
        'hidden lg:block' => $hideOnMobile && $hideOnTablet && ! $hideOnDesktop,
        'hidden md:block lg:hidden' => $hideOnMobile && ! $hideOnTablet && $hideOnDesktop,
        'hidden md:block' => $hideOnMobile && ! $hideOnTablet && ! $hideOnDesktop,
        'md:hidden' => ! $hideOnMobile && $hideOnTablet && $hideOnDesktop,
        'md:hidden lg:block' => ! $hideOnMobile && $hideOnTablet && ! $hideOnDesktop,
        'lg:hidden' => ! $hideOnMobile && ! $hideOnTablet && $hideOnDesktop,
        'space-y-4' => $spacing === 'sm',
        'space-y-2' => $spacing === 'md',
        'space-y-10' => $spacing === 'lg',
        'py-4' => in_array('sm', $padding, true),
        'pt-4' => in_array('t-sm', $padding, true),
        'pb-4' => in_array('b-sm', $padding, true),
        'py-8' => in_array('md', $padding, true),
        'pt-8' => in_array('t-md', $padding, true),
        'pb-8' => in_array('b-md', $padding, true),
        'py-10' => in_array('lg', $padding, true),
        'pt-10' => in_array('t-lg', $padding, true),
        'pb-10' => in_array('b-lg', $padding, true),
        'pt-20' => in_array('t-xl', $padding, true),
        'pb-20' => in_array('b-xl', $padding, true),
        'my-4' => in_array('sm', $margin, true),
        'mt-4' => in_array('t-sm', $margin, true),
        'mb-4' => in_array('b-sm', $margin, true),
        'my-6 lg:my-10' => in_array('md', $margin, true),
        'mt-6' => in_array('t-md', $margin, true),
        'mb-6' => in_array('b-md', $margin, true),
        'my-10' => in_array('lg', $margin, true),
        'mt-10' => in_array('t-lg', $margin, true),
        'mb-10' => in_array('b-lg', $margin, true),
        'm-20' => in_array('xl', $margin, true),
        'mt-20' => in_array('t-xl', $margin, true),
        'mb-20' => in_array('b-xl', $margin, true),
    ])
>
    @foreach ($container['widgets'] as $widgetIndex => $widgetData)
        {{-- format-ignore-start --}}
                                    @php
                                        $widget = CapellLayout::getContainerWidget(
                                            $containerKey,
                                            $widgetData['widget_key'],
                                            $widgetData['occurrence'] ?? 1,
                                        );

                                        if (! $widget) {
                                            continue;
                                        }

                                        $component = $widget->getComponent();
                                        if (! $component) {
                                            continue;
                                        }

                                        $type = $widget->getMetaComponentType();

                                        $currentColspan = $previousColspan + $colspan;
                                        if ($columnStart) {
                                            $currentColspan += $columnStart - 1;
                                        }
                                    @endphp
                                    {{-- format-ignore-end --}}
        {!! config('app.debug') ? "<!-- {$widget->key} Widget ({$widget->id}) - {$component} -->" : '' !!}

        <x-capell-layout-builder::layout.widget
            :$component
            :container-colspan="$colspan"
            :$container
            :$containerKey
            :$containerIndex
            :container-width="$colspan === 12 ? $containerWidth : ContainerWidthEnum::Full"
            :$loop
            :$type
            :$widget
            :$widgetIndex
            :$widgetData
            :page-slot="$pageSlot"
        />
    @endforeach
</div>

{{-- format-ignore-start --}}
                            @if ($backgroundImage)
                        </div>
                        @endif
                        @if ($colspan !== 12)
                    </div>

                    @if ($currentColspan === 12)
                </div>
    </div>
@endif
@endif
{{-- format-ignore-end --}}
