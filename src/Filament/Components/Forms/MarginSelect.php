<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Capell\LayoutBuilder\Enums\WidgetSpacingValue;
use Filament\Forms\Components\Select;

class MarginSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout-builder::form.margin'))
            ->multiple()
            ->afterStateHydrated(function (Select $component, mixed $state): void {
                $component->state(self::normalizeHydratedState($state));
            })
            ->options(WidgetSpacingValue::class);
    }

    public static function normalizeHydratedState(mixed $state): mixed
    {
        if (is_string($state)) {
            return [$state];
        }

        return $state;
    }
}
