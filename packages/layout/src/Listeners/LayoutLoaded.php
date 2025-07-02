<?php

declare(strict_types=1);

namespace Capell\Layout\Listeners;

use Capell\Core\Contracts\EventSubscriber;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Frontend\CapellFrontend;
use Capell\Frontend\Enums\ListenerEnum;
use Capell\Layout\CapellLayoutManager;
use Capell\Layout\Models\Widget;
use Capell\Layout\Services\Creator\LayoutLoader;
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

        $language = $context->getLanguage();

        $page = $context->getPage();

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

                try {
                    $widget = LayoutLoader::getLayoutWidget(
                        $layout,
                        $widgetKey,
                        $language,
                        $page,
                        $containerKey,
                        $occurrence,
                    );

                    if ($widget instanceof Widget) {
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
