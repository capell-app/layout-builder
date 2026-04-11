<?php

declare(strict_types=1);

namespace Capell\Layout\Support\Interceptors\Layouts;

use Capell\Core\Contracts\ModelInterceptors\LayoutInterceptorInterface;
use Capell\Core\Models\Layout;
use Capell\Layout\Enums\ContainerWidthEnum;
use Capell\Layout\Support\Creator\WidgetCreator;

final class ResultsLayoutInterceptor implements LayoutInterceptorInterface
{
    public function beforeCreate(array $data): array
    {
        return $data;
    }

    public function afterCreated(Layout $layout, array $data): void
    {
        $widgetCreator = resolve(WidgetCreator::class);
        $widgetCreator->pageContentWidget();
        $widgetCreator->breadcrumbWidget();
        $widgetCreator->pageSlotWidget();
        $widgetCreator->latestPagesWidget();

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
                'container' => ContainerWidthEnum::Full,
                'tag' => 'aside',
                'padding' => ['md'],
                'html_class' => 'sidebar-sticky space-y-8',
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
