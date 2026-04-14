<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget\Page;

use Capell\Layout\View\Components\Widget\AbstractWidget;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

abstract class AbstractPagesWidget extends AbstractWidget
{
    protected static string $defaultView = 'capell-layout::components.widget.asset.pages';

    protected Collection $pages;

    public function render(array $data = []): View|string|Closure
    {
        if ($this->skipRender) {
            return '';
        }

        $data['pages'] = $this->pages;

        return parent::render($data);
    }
}
