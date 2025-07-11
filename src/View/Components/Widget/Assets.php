<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget;

class Assets extends AbstractWidget
{
    protected static string $defaultView = 'capell-layout::components.widget.assets.index';

    protected function mountWidget(): void
    {
        if ($this->widget->assets->isEmpty()) {
            $this->skipRender = true;
        }
    }
}
