<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\View\Components;

use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class PageBuildingWidget extends Component
{
    public function __construct(
        private readonly Widget $widget,
    ) {}

    public function render(): View|string
    {
        $viewFile = $this->widget->getViewFile();

        if (! is_string($viewFile) || $viewFile === '') {
            return '';
        }

        $title = $this->widget->assets->first()?->asset?->translation?->title;

        return view($viewFile, ['title' => $title]);
    }
}
