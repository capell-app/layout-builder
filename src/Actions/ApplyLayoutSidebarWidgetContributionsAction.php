<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Contracts\LayoutSidebarWidgetContributor;
use Capell\LayoutBuilder\Data\LayoutSidebarWidgetData;
use Capell\LayoutBuilder\Models\Widget;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Layout $layout)
 */
class ApplyLayoutSidebarWidgetContributionsAction
{
    use AsFake;
    use AsObject;

    public function handle(Layout $layout): void
    {
        $containers = $layout->getAttribute('containers');

        if (! is_array($containers)) {
            $containers = [];
        }

        if (! isset($containers['sidebar']) || ! is_array($containers['sidebar'])) {
            $containers['sidebar'] = $this->defaultSidebarContainer();
        }

        $sidebarWidgets = $containers['sidebar']['widgets'] ?? [];
        $sidebarWidgets = is_array($sidebarWidgets) ? $sidebarWidgets : [];

        $sidebarWidgetKeys = $this->widgetKeys($sidebarWidgets);

        foreach ($this->contributedWidgets($layout) as $sidebarWidget) {
            if (in_array($sidebarWidget->widgetKey, $sidebarWidgetKeys, true)) {
                continue;
            }

            if (! Widget::query()->where('key', $sidebarWidget->widgetKey)->exists()) {
                continue;
            }

            $sidebarWidgets[] = $sidebarWidget->toLayoutWidget();
            $sidebarWidgetKeys[] = $sidebarWidget->widgetKey;
        }

        $containers['sidebar']['widgets'] = $sidebarWidgets;

        $layout->update([
            'containers' => $containers,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSidebarContainer(): array
    {
        return [
            'meta' => [
                'colspan' => 3,
                'override_columns' => 1,
                'container' => 'full',
                'padding' => ['md'],
                'html_class' => 'sidebar-sticky space-y-8',
            ],
            'widgets' => [],
        ];
    }

    /**
     * @return array<int, LayoutSidebarWidgetData>
     */
    private function contributedWidgets(Layout $layout): array
    {
        $layoutKey = (string) $layout->getAttribute('key');
        $widgets = [];

        foreach (app()->tagged(LayoutSidebarWidgetContributor::TAG) as $contributor) {
            if (! $contributor instanceof LayoutSidebarWidgetContributor) {
                continue;
            }

            foreach ($contributor->sidebarWidgets() as $sidebarWidget) {
                if (! $sidebarWidget->appliesTo($layoutKey)) {
                    continue;
                }

                $widgets[] = $sidebarWidget;
            }
        }

        return $widgets;
    }

    /**
     * @param  array<int, mixed>  $widgets
     * @return array<int, string>
     */
    private function widgetKeys(array $widgets): array
    {
        return collect($widgets)
            ->map(fn (mixed $widget): ?string => is_array($widget) ? ($widget['widget_key'] ?? null) : null)
            ->filter(fn (?string $widgetKey): bool => is_string($widgetKey) && $widgetKey !== '')
            ->unique()
            ->values()
            ->all();
    }
}
