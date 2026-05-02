<?php

declare(strict_types=1);

namespace Capell\Mosaic\Actions;

use Capell\Mosaic\Data\LayoutPresetData;
use Capell\Mosaic\Support\LayoutPresets\LayoutPresetRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class ListLayoutPresetsAction
{
    use AsObject;

    /**
     * @return array<int, LayoutPresetData>
     */
    public function handle(): array
    {
        return resolve(LayoutPresetRegistry::class)->all();
    }
}
