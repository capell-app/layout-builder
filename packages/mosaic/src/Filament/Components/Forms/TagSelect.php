<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms;

use Filament\Forms\Components\Select;

class TagSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-mosaic::form.tag'))
            ->default('div')
            ->options([
                'div' => __('capell-mosaic::form.tag_div'),
                'section' => __('capell-mosaic::form.tag_section'),
                'article' => __('capell-mosaic::form.tag_article'),
                'aside' => __('capell-mosaic::form.tag_aside'),
                'header' => __('capell-mosaic::form.tag_header'),
                'footer' => __('capell-mosaic::form.tag_footer'),
                'nav' => __('capell-mosaic::form.tag_nav'),
                'main' => __('capell-mosaic::form.tag_main'),
            ]);
    }
}
