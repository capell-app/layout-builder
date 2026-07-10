<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Lorisleiva\Actions\Concerns\AsObject;

final class StripLayoutPresetLinkAction
{
    use AsObject;

    /**
     * @param  array<string, mixed>  $container
     * @return array<string, mixed>
     */
    public function handle(array $container): array
    {
        $meta = is_array($container['meta'] ?? null) ? $container['meta'] : [];
        unset($meta['preset']);

        if ($meta === []) {
            unset($container['meta']);

            return $container;
        }

        $container['meta'] = $meta;

        return $container;
    }
}
