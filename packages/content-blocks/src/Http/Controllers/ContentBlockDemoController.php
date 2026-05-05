<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Http\Controllers;

use Capell\ContentBlocks\Actions\BuildContentBlockDemoDataAction;
use Illuminate\Contracts\View\View;

class ContentBlockDemoController
{
    public function __invoke(string $block): View
    {
        return view('capell-content-blocks::content-block.demo', BuildContentBlockDemoDataAction::run(str_replace('-', '_', $block)));
    }
}
