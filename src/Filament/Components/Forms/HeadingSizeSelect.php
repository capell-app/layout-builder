<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms;

class HeadingSizeSelect extends Forms\Components\Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::form.heading_size'))
            ->default('h1')
            ->options([
                'h1' => 'h1',
                'h2' => 'h2',
                'h3' => 'h3',
                'h4' => 'h4',
                'h5' => 'h5',
                'h6' => 'h6',
                'div' => 'div',
                'p' => 'p',
            ]);
    }
}
