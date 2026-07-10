<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts\WidgetExtensions;

interface WidgetExtensionStateUpcaster
{
    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function upcast(array $state, int $fromVersion, int $toVersion): array;
}
