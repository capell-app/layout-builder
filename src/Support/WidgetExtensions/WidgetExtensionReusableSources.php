<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetExtensions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetAvailability;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final class WidgetExtensionReusableSources
{
    /**
     * Pass the request's loader only while building the page that loader
     * already preloaded; revalidation callers omit it to get fresh widgets.
     *
     * @return list<array<mixed>>
     */
    public function sources(FrontendRenderContextData $context, ?LayoutLoader $loader = null): array
    {
        if (! $context->layout instanceof Layout || ! $context->language instanceof Language || ! $context->page instanceof Pageable) {
            return [];
        }

        $containers = $context->layout->getAttribute('containers');
        if (! is_array($containers)) {
            return [];
        }

        // Without a loader a new one is deliberate: buyer revalidation must not
        // inherit a previously rendered occurrence's memoised translation or eligibility.
        $loader ??= new LayoutLoader;
        $sources = [];
        $seen = [];
        foreach ($containers as $containerKey => $container) {
            if (! is_array($container)) {
                continue;
            }

            $container = array_filter($container, is_string(...), ARRAY_FILTER_USE_KEY);
            foreach (LayoutWidgetData::fromContainer($container) as $reference) {
                $key = LayoutWidgetData::key($reference);
                if ($key === null) {
                    continue;
                }

                $widget = $loader->getLayoutWidget(
                    $context->layout,
                    $key,
                    $context->language,
                    $context->page,
                    (string) $containerKey,
                    LayoutWidgetData::occurrence($reference),
                );
                if (! $widget instanceof Widget || isset($seen[$key]) || ! $this->available($widget, $context)) {
                    continue;
                }

                $seen[$key] = true;
                $translation = $widget->getRelationValue('translation');
                if (! $translation instanceof Model) {
                    continue;
                }

                $translation->setRelation('translatable', $widget);
                $content = $translation->getAttribute('content');
                if (is_array($content)) {
                    $sources[] = $content;
                }
            }
        }

        return $sources;
    }

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
                report($exception);

                return false;
            }
        }

        return true;
    }
}
