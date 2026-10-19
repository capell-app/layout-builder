<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Contracts\LayoutSidebarWidgetContributor;
use Capell\LayoutBuilder\Data\LayoutSidebarWidgetData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;
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
        $layout->getConnection()->transaction(function () use ($layout): void {
            $this->applyContributions($layout);
        });
    }

    private function applyContributions(Layout $layout): void
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
        $hasAvailableContribution = false;

        foreach ($this->contributedWidgets($layout) as $sidebarWidget) {
            if (! Widget::query()->where('key', $sidebarWidget->widgetKey)->exists()) {
                continue;
            }

            $hasAvailableContribution = true;

            if (in_array($sidebarWidget->widgetKey, $sidebarWidgetKeys, true)) {
                continue;
            }

            $sidebarWidgets[] = $sidebarWidget->toLayoutWidget();
            $sidebarWidgetKeys[] = $sidebarWidget->widgetKey;
        }

        $containers['sidebar']['widgets'] = $sidebarWidgets;
        if ($hasAvailableContribution) {
            $containers = $this->withStandardMainSlot($layout, $containers);
        }

        $layout->update([
            'containers' => $containers,
        ]);
    }

    /**
     * @param  array<string, mixed>  $containers
     * @return array<string, mixed>
     */
    private function withStandardMainSlot(Layout $layout, array $containers): array
    {
        $layoutKey = $layout->getAttribute('key');
        $sidebar = $containers['sidebar'] ?? [];
        if (! is_array($sidebar)) {
            return $containers;
        }

        $sidebarColspan = data_get($sidebar, 'meta.colspan');
        if (! in_array($layoutKey, [LayoutEnum::Default->value, LayoutEnum::Results->value], true)
            || empty($sidebar['widgets'])
            || ! is_int($sidebarColspan) || $sidebarColspan < 1 || $sidebarColspan > 11) {
            return $containers;
        }

        $widgets = $layoutKey === LayoutEnum::Default->value
            ? [['widget_key' => 'page-content', 'occurrence' => 1]]
            : [];
        $main = $containers['main'] ?? null;
        if ($main !== null) {
            $starterWidgets = $widgets === [] ? [['widget_key' => 'page-slot', 'occurrence' => 1]] : $widgets;
            // Only the framework's unconfigured starter is ours to resize.
            if ($main !== ['widgets' => $starterWidgets, 'meta' => ['colspan' => 12]]) {
                return $containers;
            }

            $widgets = $starterWidgets;
        }

        $slot = Widget::query()->where('key', 'page-slot')->first();
        $slot ??= resolve(WidgetCreator::class)->pageSlotWidget();
        if (! WidgetIsSlotAction::run($slot)) {
            return $containers;
        }

        if (! in_array('page-slot', $this->widgetKeys($widgets), true)) {
            $widgets[] = ['widget_key' => 'page-slot', 'occurrence' => 1];
        }

        $main = ['widgets' => $widgets, 'meta' => ['colspan' => 12 - $sidebarColspan]];
        if (array_key_exists('main', $containers)) {
            $containers['main'] = $main;

            return $containers;
        }

        $ordered = [];
        foreach ($containers as $key => $container) {
            if ($key === 'sidebar') {
                $ordered['main'] = $main;
            }

            $ordered[$key] = $container;
        }

        return $ordered;
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
