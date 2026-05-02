<?php

declare(strict_types=1);

namespace Capell\Mosaic\Support\Interceptors\Layouts;

use Capell\Core\Contracts\ModelInterceptors\LayoutInterceptorInterface;
use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Models\Layout;
use Capell\Mosaic\Support\Creator\WidgetCreator;
use Illuminate\Support\Facades\Schema;

final class DefaultLayoutInterceptor implements LayoutInterceptorInterface
{
    public function beforeCreate(array $data): array
    {
        return $data;
    }

    public function afterCreated(Layout $layout, array $data): void
    {
        if (! Schema::hasColumn('layouts', 'containers')) {
            return;
        }

        $widgetCreator = resolve(WidgetCreator::class);
        $widgetCreator->breadcrumbWidget();
        $widgetCreator->pageContentWidget();
        $widgetCreator->childrenWidget();
        $widgetCreator->latestPagesWidget();

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
