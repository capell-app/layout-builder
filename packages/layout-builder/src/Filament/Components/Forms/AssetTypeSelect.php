<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Capell\Core\Data\AssetData;
use Capell\Core\Facades\CapellCore;
use Filament\FormBuilder\Components\Select;

class AssetTypeSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::form.asset_types'))
            ->options(
                fn (): array => CapellCore::getAssets()
                    ->mapWithKeys(static fn (AssetData $asset): array => [$asset->getKey() => $asset->getLabel()])
                    ->all(),
            )
            ->default(fn (AssetTypeSelect $component): int|string|null => array_key_first($component->getOptions()));
    }
}
