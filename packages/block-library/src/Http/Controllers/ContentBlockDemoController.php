<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Http\Controllers;

use Capell\BlockLibrary\Actions\BuildContentBlockDemoDataAction;
use Illuminate\Contracts\View\View;

class ContentBlockDemoController
{
    public function __invoke(string $block): View
    {
        return view('capell-block-library::content-block.demo', BuildContentBlockDemoDataAction::run(str_replace('-', '_', $block)));
    }
}
