<?php

declare(strict_types=1);

namespace Capell\Mosaic\View\Components\Widget;

class Asset extends AbstractWidget
{
    protected static string $defaultView = 'capell-mosaic::components.widget.asset.index';

    protected function mountWidget(): void
    {
        if ($this->widget->assets->isEmpty() && config('capell-mosaic.widget.skip_render_empty', true)) {
            $this->skipRender = true;
        }
    }
}
