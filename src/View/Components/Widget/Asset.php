<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget;

class Asset extends AbstractWidget
{
    protected static string $defaultView = 'capell-layout::components.widget.asset.index';

    protected function mountWidget(): void
    {
        if ($this->widget->assets->isEmpty() && config('capell-layout.widget.skip_render_empty', true)) {
            $this->skipRender = true;
        }
    }
}
