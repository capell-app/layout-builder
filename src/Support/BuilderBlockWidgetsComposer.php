<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\BlockLibrary\Data\BuilderBlockRenderData;
use Capell\BlockLibrary\Support\DefaultBlockCatalog;
use Capell\Frontend\Exceptions\WidgetLibraryException;
use Capell\Frontend\Support\Render\PublicRenderDataContributorRegistry;
use Illuminate\View\View;

/** Read prepared values only; preparation belongs outside the Blade query guard. */
final class BuilderBlockWidgetsComposer
{
    public function compose(View $view): void
    {
        $data = $view->getData();
        $context = is_array($data['context'] ?? null) ? $data['context'] : [];
        $owner = $context['record'] ?? $data['page'] ?? null;
        /** @var array<int, BuilderBlockRenderData> $prepared */
        $prepared = is_array($data['builderBlockRenderData'] ?? null) ? $data['builderBlockRenderData'] : [];
        $contributor = resolve(PublicRenderDataContributorRegistry::class)->all()['builder_blocks'] ?? null;
        $store = $contributor instanceof BuilderBlockPublicRenderDataContributor ? $contributor->renderData() : null;
        $widgets = $data['widgets'] ?? [];
        foreach (is_array($widgets) ? array_values($widgets) : [] as $index => $widget) {
            if (! is_array($widget) || ! is_string($widget['type'] ?? null) || $widget['type'] === 'content' || ! in_array($widget['type'], DefaultBlockCatalog::keys(), true) || ! is_array($widget['data'] ?? null)) {
                continue;
            }

            $state = $widget['data'];
            $prepared[$index] ??= $store?->get($owner, $widget['type'], $state)
                ?? throw new WidgetLibraryException(sprintf("Render data for builder block '%s' was not prepared.", $widget['type']));
        }

        $view->with('builderBlockRenderData', $prepared);
    }
}
