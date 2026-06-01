<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Support\Htmlable;

class HtmlClassInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout-builder::form.html_class'))
            ->validationAttribute(function (TextInput $component): string {
                $label = $component->getLabel();

                return $label instanceof Htmlable ? $label->toHtml() : ($label ?? '');
            })
            ->regex('/^[a-zA-Z0-9\_\-\s\:]+$/');
    }
}
