<?php

declare(strict_types=1);

namespace Capell\Layout\Listeners;

use Capell\Core\Contracts\EventSubscriber;
use Capell\Core\Models\Layout;
use Capell\Frontend\CapellFrontend;
use Capell\Frontend\Enums\ListenerEnum;
use Capell\Layout\CapellLayoutManager;
use Capell\Layout\Models\Widget;
use Exception;
use Illuminate\Support\Facades\Log;

class LayoutLoaded implements EventSubscriber
{
    public function handle(string $event, object $context): void
    {
        if ($event !== ListenerEnum::LayoutLoaded->value) {
            return;
        }

        if (! $context instanceof CapellFrontend) {
            return;
        }

        $layout = $context->getLayout();

        if (! $layout instanceof Layout) {
            return;
        }

        $this->loadLayoutWidgets($layout);
    }

    /**
     * Load widgets from the layout and store them in the CapellLayoutManager
     */
    protected function loadLayoutWidgets(Layout $layout): void
    {
        // Clear any previously stored widgets
        CapellLayoutManager::clearContainerWidgets();

        // Get all containers from the layout
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

                try {
                    // Find the widget by key
                    $widget = Widget::where('key', $widgetKey)->first();

                    if ($widget) {
                        // Store the widget in the CapellLayoutManager
                        CapellLayoutManager::storeContainerWidget($containerKey, $widgetKey, $widget, $occurrence);
                    }
                } catch (Exception $e) {
                    Log::error('Failed to load widget: '.$e->getMessage(), [
                        'containerKey' => $containerKey,
                        'widgetKey' => $widgetKey,
                        'occurrence' => $occurrence,
                    ]);
                }
            }
        }
    }
}
