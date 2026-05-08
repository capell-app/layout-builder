<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Widget;

class Asset extends AbstractWidget
{
    protected static string $defaultView = 'capell-layout-builder::components.widget.asset.index';

    protected function mountWidget(): void
    {
        if ($this->widget->assets->isEmpty() && config('capell-layout-builder.widget.skip_render_empty', true) === true) {
            $this->skipRender = true;
        }
    }
}
