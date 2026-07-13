<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\RenderHooks;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Frontend\Contracts\RenderHookExtensionInterface;
use Capell\Frontend\Data\MainContentRenderHookData;
use Capell\Frontend\Data\RenderHookContext;
use Capell\Frontend\Support\CapellFrontendContext;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Livewire\Blaze\Blaze;

final class RegisterMainContentLayoutHook implements RenderHookExtensionInterface
{
    public const string Scenario = 'frontend-main-layout';

    public const string Target = 'capell::layout.main';

    public function render(RenderHookContext $context): string
    {
        if (! $context->item instanceof MainContentRenderHookData) {
            return '';
        }

        $containers = $context->item->layout instanceof Layout
            ? $context->item->layout->getAttribute('containers')
            : data_get($context->item->layout, 'containers');
        if (! is_array($containers) || $containers === []) {
            return '';
        }

        $this->ensureLayoutWidgetsLoaded($context->item);

        $view = view('capell-layout-builder::components.layout.main-content', [
            'context' => $context->item,
        ]);

        $wasBlazeEnabled = Blaze::isEnabled();
        Blaze::disable();

        try {
            $output = $view->render();
        } finally {
            if ($wasBlazeEnabled) {
                Blaze::enable();
            }
        }

        return is_string($output) && trim($output) !== '' ? $output : '';
    }

    private function ensureLayoutWidgetsLoaded(MainContentRenderHookData $context): void
    {
        if (! $context->layout instanceof Layout || ! $context->page instanceof Pageable) {
            return;
        }

        $containers = $context->layout->getAttribute('containers');
        if (! is_array($containers) || $containers === []) {
            return;
        }

        $containers = $this->validContainers($containers);
        if ($this->hasStoredWidgets($containers)) {
            return;
        }

        $language = $this->frontendLanguage();
        if (! $language instanceof Language) {
            return;
        }

        $loader = resolve(LayoutLoader::class);
        $loader->preloadLayoutWidgets($context->layout, $language, $context->page);

        foreach ($containers as $containerKey => $container) {
            foreach (LayoutWidgetData::fromContainer($container) as $widgetData) {
                $widgetKey = LayoutWidgetData::key($widgetData);
                if ($widgetKey === null) {
                    continue;
                }

                $occurrence = LayoutWidgetData::occurrence($widgetData);
                $widget = $loader->getLayoutWidget(
                    $context->layout,
                    $widgetKey,
                    $language,
                    $context->page,
                    (string) $containerKey,
                    $occurrence,
                );

                if ($widget instanceof Widget) {
                    CapellLayoutManager::storeContainerWidget((string) $containerKey, $widgetKey, $widget, $occurrence);
                }
            }
        }
    }

    /**
     * @param  array<mixed, mixed>  $containers
     * @return array<string, array<string, mixed>>
     */
    private function validContainers(array $containers): array
    {
        $validContainers = [];

        foreach ($containers as $containerKey => $container) {
            if (is_string($containerKey) && is_array($container)) {
                $validContainers[$containerKey] = $container;
            }
        }

        return $validContainers;
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     */
    private function hasStoredWidgets(array $containers): bool
    {
        foreach ($containers as $containerKey => $container) {
            foreach (LayoutWidgetData::fromContainer($container) as $widgetData) {
                $widgetKey = LayoutWidgetData::key($widgetData);
                if ($widgetKey === null) {
                    continue;
                }

                if (CapellLayoutManager::getStoredContainerWidget(
                    (string) $containerKey,
                    $widgetKey,
                    LayoutWidgetData::occurrence($widgetData),
                ) instanceof Widget) {
                    return true;
                }
            }
        }

        return false;
    }

    private function frontendLanguage(): ?Language
    {
        $context = app()->bound('capell.frontend.context')
            ? resolve('capell.frontend.context')
            : (app()->bound(CapellFrontendContext::class) ? resolve(CapellFrontendContext::class) : null);

        if (! is_object($context) || ! method_exists($context, 'language')) {
            return null;
        }

        $language = $context->language();

        return $language instanceof Language ? $language : null;
    }
}
