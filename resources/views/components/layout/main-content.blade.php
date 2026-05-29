@php
    use Capell\Frontend\Data\MainContentRenderHookData;
    use Capell\LayoutBuilder\Actions\BlockIsSlotAction;
    use Capell\LayoutBuilder\Actions\ResolveLayoutAreaContainersAction;
    use Capell\LayoutBuilder\Models\Widget;
    use Capell\LayoutBuilder\Support\CapellLayoutManager;
    use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
    use Capell\LayoutBuilder\Support\LayoutBlockData;

    /** @var MainContentRenderHookData $context */
    $previousColspan = null;
@endphp

@if ($context->layout?->containers)
    @foreach (ResolveLayoutAreaContainersAction::run($context->layout->containers, LayoutAreaRegistry::MAIN) as $containerKey => $container)
        @php
            $layoutBlocks = collect($container['widgets'] ?? $container['blocks'] ?? [])
                ->map(static fn (mixed $blockData): array => LayoutBlockData::normalize($blockData))
                ->filter(static fn (array $blockData): bool => LayoutBlockData::key($blockData) !== null)
                ->map(static fn (array $blockData): ?Widget => CapellLayoutManager::getStoredContainerBlock(
                    (string) $containerKey,
                    (string) LayoutBlockData::key($blockData),
                    LayoutBlockData::occurrence($blockData),
                ))
                ->filter();

            if ($layoutBlocks->isEmpty()) {
                continue;
            }

            $context->pageContentBlockRendered = $context->pageContentBlockRendered || collect($container['widgets'] ?? $container['blocks'] ?? [])
                ->map(static fn (mixed $blockData): array => LayoutBlockData::normalize($blockData))
                ->contains(static fn (array $blockData): bool => LayoutBlockData::key($blockData) === 'page-content');

            $hasSlotBlock = ! $context->slotRendered && $layoutBlocks->contains(
                static fn (Widget $layoutBlock): bool => BlockIsSlotAction::run($layoutBlock),
            );

            $colspan = (int) ($container['meta']['colspan'] ?? 12);
            $columnStart = (int) ($container['meta']['column_start'] ?? 0);
            $htmlClass = $container['meta']['html_class'] ?? '';

            if ($context->containerClass) {
                if (is_string($context->containerClass)) {
                    $htmlClass .= ' ' . $context->containerClass;
                } elseif (! empty($context->containerClass[$containerKey])) {
                    $htmlClass .= ' ' . $context->containerClass[$containerKey];
                }
            }
        @endphp

        @include('capell-layout-builder::components.layout.container', [
            'container' => $container,
            'containerKey' => (string) $containerKey,
            'layout' => $context->layout,
            'page' => $context->page,
            'containerIndex' => $loop->index,
            'colspan' => $colspan,
            'columnStart' => $columnStart,
            'htmlClass' => $htmlClass,
            'pageSlot' => $hasSlotBlock ? $context->pageSlot : null,
            'previousColspan' => $previousColspan,
        ])

        @php
            if ($hasSlotBlock && $context->pageSlot) {
                $context->slotRendered = true;
            }

            $previousColspan += $colspan;
            if ($columnStart) {
                $previousColspan += $columnStart - 1;
            }
            $previousColspan = $previousColspan >= 12 ? 0 : $previousColspan;
        @endphp
    @endforeach
@endif

@if ($previousColspan && $previousColspan !== 12)
    </div>
    </div>
@endif
