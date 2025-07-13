<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms;

class ColumnInput extends Forms\Components\TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->numeric()
            ->maxValue(12)
            ->minValue(0);
    }
}
