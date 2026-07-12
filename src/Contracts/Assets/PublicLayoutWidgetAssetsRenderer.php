<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts\Assets;

interface PublicLayoutWidgetAssetsRenderer
{
    /**
     * @param  array<string, mixed>  $widgetData
     * @param  array<string, mixed>  $options
     */
    public function render(
        mixed $widget,
        string $containerKey,
        array $widgetData = [],
        mixed $widgetAssets = null,
        mixed $widgetAssetsByWidget = null,
        array $options = [],
    ): string;
}
