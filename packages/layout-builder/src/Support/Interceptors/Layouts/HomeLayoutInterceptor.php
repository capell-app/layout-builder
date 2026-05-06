<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Interceptors\Layouts;

use Capell\Core\Contracts\ModelInterceptors\LayoutInterceptorInterface;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;
use Illuminate\Support\Facades\Schema;

final class HomeLayoutInterceptor implements LayoutInterceptorInterface
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
        $widgetCreator->pageContentWidget();

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
}
