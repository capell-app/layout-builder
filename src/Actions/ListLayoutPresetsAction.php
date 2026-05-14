<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Data\LayoutPresetData;
use Capell\LayoutBuilder\Support\LayoutPresets\LayoutPresetRegistry;
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
