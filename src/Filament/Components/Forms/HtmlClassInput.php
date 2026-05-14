<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Filament\Forms\Components\TextInput;

class HtmlClassInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout-builder::form.html_class'))
            ->validationAttribute(fn (TextInput $component): string => $component->getLabel())
            ->regex('/^[a-zA-Z0-9\_\-\s\:]+$/');
    }
}
