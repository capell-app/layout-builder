<?php

declare(strict_types=1);

namespace Capell\Layout\Listeners;

use Capell\Core\Contracts\EventSubscriber;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Frontend\Enums\ListenerEnum;
use Capell\Frontend\Facades\Frontend;
use Capell\Layout\CapellLayoutManager;
use Capell\Layout\Models\Widget;
use Capell\Layout\Services\Creator\LayoutLoader;

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

        if (! $layout instanceof Layout || ! $language instanceof Language || ! $page instanceof Page) {
            return;
        }

        $this->loadLayoutWidgets($layout, $page, $language);
    }

    protected function loadLayoutWidgets(Layout $layout, Page $page, Language $language): void
    {
        CapellLayoutManager::clearContainerWidgets();

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

                $widget = LayoutLoader::getLayoutWidget(
                    $layout,
                    $widgetKey,
                    $language,
                    $page,
                    $containerKey,
                    $occurrence,
                );

                if (! $widget instanceof Widget) {
                    CapellCore::log(
                        'Widget not found for layout',
                        context: [
                            'containerKey' => $containerKey,
                            'widgetKey' => $widgetKey,
                            'occurrence' => $occurrence,
                        ],
                        type: 'error',
                    );
                }

                CapellLayoutManager::storeContainerWidget($containerKey, $widgetKey, $widget, $occurrence);
            }
        }
    }
}
