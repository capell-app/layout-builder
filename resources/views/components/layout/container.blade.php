@php
    use Capell\Core\Enums\ContainerWidthEnum;
    use Capell\Frontend\Actions\GetLayoutContainerWidthAction;
    use Capell\LayoutBuilder\Enums\ContainerAlignmentEnum;
    use Capell\LayoutBuilder\Enums\ElementComponentEnum;
    use Capell\LayoutBuilder\Enums\ResponsiveVisibilityEnum;
    use Capell\LayoutBuilder\Support\CapellLayoutManager;
    use Capell\LayoutBuilder\Support\LayoutElementData;

    $containerWidth = ! empty($container['meta']['container'])
        ? ContainerWidthEnum::from($container['meta']['container'])
        : GetLayoutContainerWidthAction::run();
    $spacing = $container['meta']['spacing'] ?? null;
    $padding = $container['meta']['padding'] ?? [];
    $margin = $container['meta']['margin'] ?? [];
    $htmlClass = trim((string) ($htmlClass ?? ''));

    if (! empty($container['meta']['html_class'])) {
        $htmlClass = trim($htmlClass . ' ' . $container['meta']['html_class']);
    }

    $alignment = ContainerAlignmentEnum::tryFrom((string) ($container['meta']['alignment'] ?? ''))
        ?? ContainerAlignmentEnum::Stretch;

    $hiddenOn = (array) ($container['meta']['hidden_on'] ?? []);
    $hideOnMobile = in_array(ResponsiveVisibilityEnum::Mobile->value, $hiddenOn, true);
    $hideOnTablet = in_array(ResponsiveVisibilityEnum::Tablet->value, $hiddenOn, true);
    $hideOnDesktop = in_array(ResponsiveVisibilityEnum::Desktop->value, $hiddenOn, true);

    $currentColspan = (int) $colspan;
    $pageMeta = is_array($page?->meta ?? null) ? $page->meta : [];
    $showHero = ! array_key_exists('show_hero', $pageMeta) || $pageMeta['show_hero'] !== false;
@endphp

@if ($colspan === 12 && $previousColspan && $previousColspan !== 12)
    </div>
    </div>
@endif

@if ($colspan !== 12)
    @if (! $previousColspan || $previousColspan === 12)
        <div @class([
            'capell-layout-builder-layout-container',
            $containerWidth->getContainerClass(),
        ])>
            <div class="flex w-full min-w-0 flex-col gap-x-12 lg:grid lg:grid-cols-12 xl:gap-x-16">
    @endif

    <div
        @class([
            'min-w-0 lg:col-span-[var(--colspan)]',
            'lg:col-start-[var(--column-start)]',
        ])
        style="--colspan: {{ $colspan }}; --column-start: {{ $columnStart }};"
    >
@endif

<div
    id="layout-container-{{ $containerKey }}"
    @class([
        'layout-container',
        $htmlClass => $htmlClass !== '',
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
    @foreach (LayoutElementData::normalizeMany($container['elements'] ?? []) as $elementIndex => $elementData)
        @php
            $elementKey = LayoutElementData::key($elementData);
            if ($elementKey === null) {
                continue;
            }

            $element = CapellLayoutManager::getStoredContainerElement(
                (string) $containerKey,
                $elementKey,
                LayoutElementData::occurrence($elementData),
            );

            if (! $element) {
                continue;
            }

            $component = $element->getComponent();
            if (! $component) {
                continue;
            }

            $componentKey = (string) $component;
            if (! $showHero && in_array($componentKey, [
                ElementComponentEnum::Hero->value,
                ElementComponentEnum::BannerImage->value,
                ElementComponentEnum::ApHeroBanner->value,
            ], true)) {
                continue;
            }

            $type = $element->getMetaComponentType();
            $currentColspan = (int) $previousColspan + (int) $colspan;
            if ($columnStart) {
                $currentColspan += $columnStart - 1;
            }
        @endphp

        @include('capell-layout-builder::components.layout.element', [
            'component' => $component,
            'containerColspan' => $colspan,
            'container' => $container,
            'containerKey' => (string) $containerKey,
            'containerIndex' => $containerIndex,
            'containerWidth' => $colspan === 12 ? $containerWidth : ContainerWidthEnum::Full,
            'loop' => $loop,
            'layout' => $layout,
            'type' => $type,
            'element' => $element,
            'elementIndex' => $elementIndex,
            'elementData' => $elementData,
            'pageSlot' => $pageSlot,
        ])
    @endforeach
</div>

@if ($colspan !== 12)
    </div>

    @if ($currentColspan === 12)
            </div>
        </div>
    @endif
@endif
