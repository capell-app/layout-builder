<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms;

use Filament\Forms\Components\TextInput;

class HtmlClassInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-mosaic::form.html_class'))
            ->validationAttribute(fn (TextInput $component): string => $component->getLabel())
            ->regex('/^[a-zA-Z0-9\_\-\s\:]+$/');
    }
}
