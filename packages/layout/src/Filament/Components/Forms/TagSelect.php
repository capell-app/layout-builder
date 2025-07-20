<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms\Components\Select;

class TagSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout::form.tag'))
            ->default('div')
            ->options([
                'div' => __('capell-layout::form.tag_div'),
                'section' => __('capell-layout::form.tag_section'),
                'article' => __('capell-layout::form.tag_article'),
                'aside' => __('capell-layout::form.tag_aside'),
                'header' => __('capell-layout::form.tag_header'),
                'footer' => __('capell-layout::form.tag_footer'),
                'nav' => __('capell-layout::form.tag_nav'),
                'main' => __('capell-layout::form.tag_main'),
            ]);
    }
}
