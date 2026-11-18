<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Frontend\Contracts\PublicLayoutRenderPreparer;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\Frontend\Data\PublicPageRenderData;
use Capell\Frontend\Support\Render\PublicRenderDataScope;
use Capell\LayoutBuilder\Actions\ResolvePublicWidgetRenderContextAction;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetAvailability;
use Capell\LayoutBuilder\Data\PublicWidgetRenderContextData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Override;
use Throwable;
use WeakMap;

/**
 * Request-only contexts: never persist references or versions in the public graph.
 * Prepared state belongs to one render's data object and is read through Core's
 * PublicRenderDataScope, so nested renders keep their own state and nothing
 * leaks to a later render or a standalone include.
 */
final class LayoutBuilderPublicLayoutRenderPreparer implements PublicLayoutRenderPreparer
{
    /**
     * Contexts keyed by container, then widget key and occurrence: the caller's
     * loop index is a DOM input, never the identity of prepared widget data.
     *
     * @var WeakMap<PublicPageRenderData, array{layout: Layout, contexts: array<string, array<string, array{index: int|string, context: PublicWidgetRenderContextData}>>}>
     */
    private WeakMap $prepared;

    public function __construct()
    {
        $this->prepared = new WeakMap;
    }

    #[Override]
    public function prepare(FrontendRenderContextData $context): void
    {
        $data = $context->publicRenderData;
        if (! $data instanceof PublicPageRenderData || ! $context->layout instanceof Layout || ! $context->language instanceof Language) {
            return;
        }

        $loader = resolve(LayoutLoader::class);
        $loader->preloadLayoutWidgets($context->layout, $context->language, $context->page);
        CapellLayoutManager::clearContainerWidgets();
        $contexts = [];

        foreach ($context->layout->containers ?? [] as $containerKey => $container) {
            if (! is_array($container)) {
                continue;
            }

            foreach (LayoutWidgetData::normalizeMany($container['widgets'] ?? []) as $widgetIndex => $widgetData) {
                $widgetKey = LayoutWidgetData::key($widgetData);
                if ($widgetKey === null) {
                    continue;
                }

                $occurrence = LayoutWidgetData::occurrence($widgetData);
                $widget = $loader->getLayoutWidget(
                    $context->layout,
                    $widgetKey,
                    $context->language,
                    $context->page,
                    (string) $containerKey,
                    $occurrence,
                );
                if (! $widget instanceof Widget) {
                    continue;
                }

                CapellLayoutManager::storeContainerWidget((string) $containerKey, $widgetKey, $widget, $occurrence);
                $renderContext = ResolvePublicWidgetRenderContextAction::run(
                    layout: $context->layout,
                    containerKey: (string) $containerKey,
                    widgetIndex: $widgetIndex,
                    widget: $widget,
                    widgetData: $widgetData,
                    type: $widget->getMetaComponentType(),
                    hasPreparedHtml: $data->preparedWidgetHtml((string) $containerKey, $widgetKey, $occurrence) !== null,
                );

                if (! $this->available($widget, $context)) {
                    $renderContext = new PublicWidgetRenderContextData(
                        occurrence: $renderContext->occurrence,
                        widgetDomId: $renderContext->widgetDomId,
                        presentation: $renderContext->presentation,
                        isLazyFragment: $renderContext->isLazyFragment,
                        widgetReference: null,
                        resourcePublicIds: [],
                        interactions: [],
                        unavailable: true,
                    );
                }

                $contexts[(string) $containerKey][$this->identity($widgetKey, $occurrence)] = ['index' => $widgetIndex, 'context' => $renderContext];
            }
        }

        $this->prepared[$data] = ['layout' => $context->layout, 'contexts' => $contexts];
    }

    /** @return array{index: int|string, context: PublicWidgetRenderContextData}|null */
    public function context(?object $layout, string $containerKey, string $widgetKey, int $occurrence): ?array
    {
        $entry = $this->active($layout);

        return $entry['contexts'][$containerKey][$this->identity($widgetKey, $occurrence)] ?? null;
    }

    public function publicRenderData(?object $layout): ?PublicPageRenderData
    {
        return $this->active($layout) === null ? null : PublicRenderDataScope::current();
    }

    /** @return array{layout: Layout, contexts: array<string, array<string, array{index: int|string, context: PublicWidgetRenderContextData}>>}|null */
    private function active(?object $layout): ?array
    {
        $data = PublicRenderDataScope::current();
        if ($layout === null || ! $data instanceof PublicPageRenderData || ! isset($this->prepared[$data])) {
            return null;
        }

        $entry = $this->prepared[$data];

        return $entry['layout'] === $layout ? $entry : null;
    }

    /**
     * Runs before the public query guard, so availability checks may query. A check
     * that throws is reported and leaves out that widget only.
     */
    private function available(Widget $widget, FrontendRenderContextData $context): bool
    {
        foreach (app()->tagged(PublicLayoutWidgetAvailability::TAG) as $availability) {
            if (! $availability instanceof PublicLayoutWidgetAvailability) {
                continue;
            }

            try {
                if (! $availability->available($widget, $context)) {
                    return false;
                }
            } catch (Throwable $exception) {
                // One failing package check hides only the widget it was asked about.
                report($exception);

                return false;
            }
        }

        return true;
    }

    private function identity(string $widgetKey, int $occurrence): string
    {
        return $widgetKey . "\0" . $occurrence;
    }
}
