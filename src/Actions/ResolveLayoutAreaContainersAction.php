<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResolveLayoutAreaContainersAction
{
    use AsObject;

    public function __construct(private readonly LayoutAreaRegistry $areas) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function handle(mixed $containers, string $area = LayoutAreaRegistry::MAIN): array
    {
        if (! is_array($containers)) {
            return [];
        }

        $areaKey = $this->areas->normalizeAreaKey($area);

        return collect($containers)
            ->filter(fn (mixed $container): bool => is_array($container) && $this->areas->containerArea($container) === $areaKey)
            ->map(fn (mixed $container): array => is_array($container) ? $container : [])
            ->all();
    }
}
