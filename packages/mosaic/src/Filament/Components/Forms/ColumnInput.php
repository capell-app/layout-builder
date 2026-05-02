<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms;

use Filament\Forms\Components\TextInput;

class ColumnInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->numeric()
            ->maxValue(12)
            ->minValue(0);
    }
}
