<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Filament\Forms\Components\FileUpload;
use Override;

final class WidgetFileUpload extends FileUpload
{
    /** @return array<mixed> */
    #[Override]
    public function getValidationRules(): array
    {
        // Schemas collect rules before Livewire reads validation data. A save
        // can arrive with a path update before any upload method reads state.
        $this->getRawState();

        return parent::getValidationRules();
    }

    #[Override]
    public function getRawState(): mixed
    {
        $state = parent::getRawState();

        // Single-file widget data persists as a path. A lazy Livewire request
        // can supply that path without running Filament's initial hydration
        // hooks, while upload methods always iterate the raw array state.
        if (is_string($state)) {
            $this->state($state);

            return parent::getRawState();
        }

        return $state;
    }
}
