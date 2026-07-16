<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Data\LayoutPresetData;
use Capell\LayoutBuilder\Support\LayoutPresets\LayoutPresetRegistry;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static list<LayoutPresetData> run()
 */
class ListLayoutPresetsAction
{
    use AsFake;
    use AsObject;

    /**
     * @return list<LayoutPresetData>
     */
    public function handle(): array
    {
        return array_values(resolve(LayoutPresetRegistry::class)->all());
    }
}
