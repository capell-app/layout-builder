<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Listeners;

use Capell\Core\Contracts\EventSubscriber;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\LayoutBlockData;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;

class LayoutLoaded implements EventSubscriber
{
    private const string FRONTEND_CONTEXT_SERVICE = 'capell.frontend.context';

    public function handle(string $event, object $context): void
    {
        if ($event !== 'loadedLayout') {
            return;
        }

        $frontend = $this->frontendContext();
        if ($frontend === null) {
            return;
        }

        $layout = method_exists($frontend, 'layout') ? $frontend->layout() : null;
        $language = method_exists($frontend, 'language') ? $frontend->language() : null;
        $page = method_exists($frontend, 'page') ? $frontend->page() : null;

        if (! $layout instanceof Layout || ! $language instanceof Language || ! $page instanceof Pageable) {
            return;
        }

        $this->loadLayoutBlocks($layout, $page, $language);
    }

    protected function loadLayoutBlocks(Layout $layout, Pageable $page, Language $language): void
    {
        CapellLayoutManager::clearContainerBlocks();

        // Preload all blocks/assets once to minimize queries during iteration
        $loader = resolve(LayoutLoader::class);
        $loader->preloadLayoutBlocks($layout, $language, $page);

        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        foreach ($containers as $containerKey => $container) {
            foreach (LayoutBlockData::fromContainer($container) as $blockData) {
                $widgetKey = LayoutBlockData::key($blockData);
                if ($widgetKey === null) {
                    continue;
                }

                $occurrence = LayoutBlockData::occurrence($blockData);

                $block = $loader->getLayoutBlock(
                    $layout,
                    $widgetKey,
                    $language,
                    $page,
                    $containerKey,
                    $occurrence,
                );

                if (! $block instanceof Widget) {
                    continue;
                }

                CapellLayoutManager::storeContainerBlock($containerKey, $widgetKey, $block, $occurrence);
            }
        }
    }

    private function frontendContext(): ?object
    {
        if (! app()->bound(self::FRONTEND_CONTEXT_SERVICE)) {
            return null;
        }

        $frontend = resolve(self::FRONTEND_CONTEXT_SERVICE);

        return is_object($frontend) ? $frontend : null;
    }
}
