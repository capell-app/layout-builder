<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms;

class HtmlClassInput extends Forms\Components\TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::form.html_class'))
            ->validationAttribute(fn (Forms\Components\TextInput $component): string => $component->getLabel())
            ->regex('/^[a-zA-Z0-9\_\-\s\:]+$/');
    }
}
