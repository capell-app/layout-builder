<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Capell\LayoutBuilder\Actions\NormalizeLayoutContainerPaddingAction;
use Capell\LayoutBuilder\Enums\WidgetSpacingValue;
use Filament\Forms\Components\Select;

class PaddingSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout-builder::form.padding'))
            ->multiple()
            ->afterStateHydrated(function (Select $component, mixed $state): void {
                $component->state(self::normalizeHydratedState($state));
            })
            ->afterStateUpdated(function (Select $component, mixed $state): void {
                $component->state(self::normalizeHydratedState($state));
            })
            ->dehydrateStateUsing(self::normalizeHydratedState(...))
            ->placeholder(__('capell-layout-builder::form.theme_default'))
            ->options(WidgetSpacingValue::class);
    }

    /**
     * @return list<string>|null
     */
    public static function normalizeHydratedState(mixed $state): ?array
    {
        return NormalizeLayoutContainerPaddingAction::make()->handle($state);
    }
}
