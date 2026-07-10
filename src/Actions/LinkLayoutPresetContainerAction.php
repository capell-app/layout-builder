<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Data\LayoutPresetLinkData;
use Lorisleiva\Actions\Concerns\AsObject;

final class LinkLayoutPresetContainerAction
{
    use AsObject;

    /**
     * @param  array<string, mixed>  $container
     * @return array<string, mixed>
     */
    public function handle(array $container, LayoutPresetLinkData $link): array
    {
        $meta = is_array($container['meta'] ?? null) ? $container['meta'] : [];
        $meta['preset'] = $link->toArray();
        $container['meta'] = $meta;

        return $container;
    }
}
