<?php

declare(strict_types=1);

namespace Capell\Layout\Services\Creator;

use Capell\Admin\Enums\LayoutEnum;
use Capell\Admin\Services\Creator\LayoutCreator as BaseLayoutCreator;
use Capell\Core\Models\Layout;
use InvalidArgumentException;

class LayoutCreator
{
    public function setup(?string $key = null): void
    {
        if ($key === null) {
            $this->defaultLayout(Layout::query()->firstWhere('key', LayoutEnum::Default));
            $this->homeLayout(Layout::query()->firstWhere('key', LayoutEnum::Home));
            $this->resultsLayout(Layout::query()->firstWhere('key', LayoutEnum::Results));

            return;
        }

        match ($key) {
            LayoutEnum::Home->value => $this->homeLayout(Layout::query()->firstWhere('key', LayoutEnum::Home)),
            LayoutEnum::Results->value => $this->resultsLayout(Layout::query()->firstWhere('key', LayoutEnum::Results)),
            LayoutEnum::Default->value => $this->defaultLayout(Layout::query()->firstWhere('key', LayoutEnum::Default)),
            default => throw new InvalidArgumentException('Invalid layout key: ' . $key)
        };
    }

    /**
     * Create a layout (if missing) and immediately set up its containers/widgets.
     */
    public function createWithContainers(string $key, bool $createWidgets = false): Layout
    {
        $layout = app(BaseLayoutCreator::class)->create($key);
        match ($key) {
            LayoutEnum::Home->value => $this->homeLayout($layout, $createWidgets),
            LayoutEnum::Results->value => $this->resultsLayout($layout, $createWidgets),
            LayoutEnum::Default->value => $this->defaultLayout($layout, $createWidgets),
            default => throw new InvalidArgumentException('Invalid layout key: ' . $key)
        };

        return $layout;
    }

    public function defaultLayout(Layout $layout, bool $createWidgets = false): void
    {
        if ($createWidgets) {
            $widgetCreator = resolve(WidgetCreator::class);
            $widgetCreator->breadcrumbWidget();
            $widgetCreator->pageContentWidget();
            $widgetCreator->childrenWidget();
            $widgetCreator->latestPagesWidget();
        }

        $layout->update([
            'containers' => [
                'main' => $this->mainContainer([
                    ['widget_key' => 'breadcrumbs'],
                    ['widget_key' => 'page-content'],
                    ['widget_key' => 'children'],
                ]),
                'sidebar' => $this->sidebarContainer([
                    ['widget_key' => 'latest-pages'],
                ]),
            ],
        ]);
    }

    public function homeLayout(Layout $layout, bool $createWidgets = false): void
    {
        if ($createWidgets) {
            $widgetCreator = resolve(WidgetCreator::class);
            $widgetCreator->pageContentWidget();
        }

        $layout->update([
            'containers' => [
                'main' => [
                    'widgets' => [
                        ['widget_key' => 'page-content'],
                    ],
                ],
            ],
        ]);
    }

    public function resultsLayout(Layout $layout, bool $createWidgets = false): void
    {
        if ($createWidgets) {
            $widgetCreator = resolve(WidgetCreator::class);
            $widgetCreator->pageContentWidget();
            $widgetCreator->breadcrumbWidget();
            $widgetCreator->pageSlotWidget();
            $widgetCreator->latestPagesWidget();
        }

        $layout->update([
            'containers' => [
                'main' => $this->mainContainer([
                    ['widget_key' => 'breadcrumbs'],
                    ['widget_key' => 'page-content'],
                    ['widget_key' => 'page-slot'],
                ]),
                'sidebar' => $this->sidebarContainer([
                    ['widget_key' => 'latest-pages'],
                ]),
            ],
        ]);
    }

    private function sidebarContainer(array $widgets): array
    {
        return [
            'meta' => [
                'colspan' => 3,
                'override_columns' => 1,
                'container' => 'full',
                'tag' => 'aside',
                'padding' => ['md'],
                'html_class' => 'sidebar-sticky space-y-10',
            ],
            'widgets' => $widgets,
        ];
    }

    private function mainContainer(array $widgets): array
    {
        return [
            'meta' => [
                'colspan' => 9,
            ],
            'widgets' => $widgets,
        ];
    }
}
