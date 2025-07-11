<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget\Pages;

use Capell\Layout\View\Components\Widget\AbstractWidget;
use Illuminate\Support\Collection;

abstract class AbstractPagesWidget extends AbstractWidget
{
    protected static string $defaultView = 'capell-layout::components.widget.page.pages';

    protected Collection $pages;

    public function render(array $data = [])
    {
        if ($this->skipRender) {
            return '';
        }

        $data['pages'] = $this->pages;

        return parent::render($data);
    }
}
