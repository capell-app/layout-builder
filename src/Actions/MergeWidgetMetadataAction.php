<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** Preserve omitted settings while replacing submitted ordered collections atomically. */
final class MergeWidgetMetadataAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  array<array-key, mixed>  $existing
     * @param  array<array-key, mixed>  $submitted
     * @return array<array-key, mixed>
     */
    public function handle(array $existing, array $submitted): array
    {
        foreach ($submitted as $key => $value) {
            $existing[$key] = is_array($value) && ! array_is_list($value) && is_array($existing[$key] ?? null)
                ? $this->handle($existing[$key], $value)
                : $value;
        }

        return $existing;
    }
}
