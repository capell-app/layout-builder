<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Listeners;

use Capell\Core\Contracts\EventSubscriber;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\LayoutElementData;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;

class LayoutLoaded implements EventSubscriber
{
    private const FRONTEND_CONTEXT_SERVICE = 'capell.frontend.context';

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

        $this->loadLayoutElements($layout, $page, $language);
    }

    protected function loadLayoutElements(Layout $layout, Pageable $page, Language $language): void
    {
        CapellLayoutManager::clearContainerElements();

        // Preload all elements/assets once to minimize queries during iteration
        $loader = resolve(LayoutLoader::class);
        $loader->preloadLayoutElements($layout, $language, $page);

        $containers = $layout->getAttribute('containers');
        $containers = is_array($containers) ? $containers : [];

        foreach ($containers as $containerKey => $container) {
            foreach (LayoutElementData::normalizeMany($container['elements'] ?? []) as $elementData) {
                $elementKey = LayoutElementData::key($elementData);
                if ($elementKey === null) {
                    continue;
                }

                $occurrence = LayoutElementData::occurrence($elementData);

                $element = $loader->getLayoutElement(
                    $layout,
                    $elementKey,
                    $language,
                    $page,
                    $containerKey,
                    $occurrence,
                );

                if (! $element instanceof Element) {
                    continue;
                }

                CapellLayoutManager::storeContainerElement($containerKey, $elementKey, $element, $occurrence);
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
