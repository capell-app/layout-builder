<?php

declare(strict_types=1);

namespace Capell\Mosaic\Listeners;

use Capell\Core\Contracts\EventSubscriber;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Frontend\Enums\ListenerEnum;
use Capell\Frontend\Facades\Frontend;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Support\CapellLayoutManager;
use Capell\Mosaic\Support\Loader\LayoutLoader;

class LayoutLoaded implements EventSubscriber
{
    public function handle(string $event, object $context): void
    {
        if ($event !== ListenerEnum::LayoutLoaded->value) {
            return;
        }

        $layout = Frontend::layout();
        $language = Frontend::language();
        $page = Frontend::page();

        if (! $layout instanceof Layout || ! $language instanceof Language || ! $page instanceof Pageable) {
            return;
        }

        $this->loadLayoutWidgets($layout, $page, $language);
    }

    protected function loadLayoutWidgets(Layout $layout, Pageable $page, Language $language): void
    {
        CapellLayoutManager::clearContainerWidgets();

        // Preload all widgets/assets once to minimize queries during iteration
        $loader = new LayoutLoader;
        $loader->preloadLayoutWidgets($layout, $language, $page);

        $containers = $layout->containers ?? [];

        foreach ($containers as $containerKey => $container) {
            if (! isset($container['widgets'])) {
                continue;
            }

            if (! is_array($container['widgets'])) {
                continue;
            }

            foreach ($container['widgets'] as $widgetData) {
                if (! isset($widgetData['widget_key'])) {
                    continue;
                }

                $widgetKey = $widgetData['widget_key'];
                $occurrence = $widgetData['occurrence'] ?? 1;

                $widget = $loader->getLayoutWidget(
                    $layout,
                    $widgetKey,
                    $language,
                    $page,
                    $containerKey,
                    $occurrence,
                );

                if (! $widget instanceof Widget) {
                    continue;
                }

                CapellLayoutManager::storeContainerWidget($containerKey, $widgetKey, $widget, $occurrence);
            }
        }
    }
}
