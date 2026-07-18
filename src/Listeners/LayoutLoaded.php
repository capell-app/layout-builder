<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Listeners;

use Capell\Core\Contracts\EventSubscriber;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;

class LayoutLoaded implements EventSubscriber
{
    public function handle(string $event, object $context): void
    {
        if ($event !== 'loadedLayout') {
            return;
        }

        $frontend = $this->frontendContext();
        if ($frontend === null) {
            return;
        }

        $layout = $frontend->layout();
        $language = $frontend->language();
        $page = $frontend->page();

        if (! $layout instanceof Layout || ! $language instanceof Language || ! $page instanceof Pageable) {
            return;
        }

        $this->loadLayoutWidgets($layout, $page, $language);
    }

    protected function loadLayoutWidgets(Layout $layout, Pageable $page, Language $language): void
    {
        CapellLayoutManager::clearContainerWidgets();

        // Preload all widgets/assets once to minimize queries during iteration
        $loader = resolve(LayoutLoader::class);
        $loader->preloadLayoutWidgets($layout, $language, $page);

        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        foreach ($containers as $containerKey => $container) {
            foreach (LayoutWidgetData::fromContainer($container) as $widgetData) {
                $widgetKey = LayoutWidgetData::key($widgetData);
                if ($widgetKey === null) {
                    continue;
                }

                $occurrence = LayoutWidgetData::occurrence($widgetData);

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

    private function frontendContext(): ?FrontendContextReader
    {
        if (! app()->bound(FrontendContextReader::class)) {
            return null;
        }

        $frontend = resolve(FrontendContextReader::class);

        return $frontend instanceof FrontendContextReader ? $frontend : null;
    }
}
