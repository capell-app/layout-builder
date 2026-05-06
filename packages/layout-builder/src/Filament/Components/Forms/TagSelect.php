<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Filament\FormBuilder\Components\Select;

class TagSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout-builder::form.tag'))
            ->default('div')
            ->options([
                'div' => __('capell-layout-builder::form.tag_div'),
                'section' => __('capell-layout-builder::form.tag_section'),
                'article' => __('capell-layout-builder::form.tag_article'),
                'aside' => __('capell-layout-builder::form.tag_aside'),
                'header' => __('capell-layout-builder::form.tag_header'),
                'footer' => __('capell-layout-builder::form.tag_footer'),
                'nav' => __('capell-layout-builder::form.tag_nav'),
                'main' => __('capell-layout-builder::form.tag_main'),
            ]);
    }
}
