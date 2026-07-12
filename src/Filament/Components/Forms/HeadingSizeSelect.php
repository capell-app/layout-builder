<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Capell\LayoutBuilder\Enums\HeadingTag;
use Filament\Forms\Components\Select;

class HeadingSizeSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout-builder::form.heading_size'))
            ->default('h1')
            ->options(HeadingTag::class);
    }
}
