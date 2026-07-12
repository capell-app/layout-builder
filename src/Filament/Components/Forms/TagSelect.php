<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Capell\LayoutBuilder\Enums\HtmlTag;
use Filament\Forms\Components\Select;

class TagSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout-builder::form.tag'))
            ->default('div')
            ->options(HtmlTag::class);
    }
}
