<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class PackageAlert extends Component
{
    public function render(): View|string
    {
        return '<div>Package alert</div>';
    }
}
