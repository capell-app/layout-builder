<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Capell\Core\Data\AssetData;
use Capell\Core\Facades\CapellCore;
use Filament\Forms\Components\ToggleButtons;

class AssetTypeToggleButtons extends ToggleButtons
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::form.asset_types'))
            ->hiddenLabel()
            ->options(
                fn (): array => CapellCore::getAssets()
                    ->mapWithKeys(static fn (AssetData $asset): array => [$asset->getKey() => $asset->getLabel()])
                    ->toArray()
            )
            ->inline()
            ->autoDefault();
    }
}
