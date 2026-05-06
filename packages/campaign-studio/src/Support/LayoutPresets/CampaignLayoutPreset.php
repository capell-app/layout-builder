<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Support\LayoutPresets;

abstract class CampaignLayoutPreset
{
    abstract public function key(): string;

    abstract public function name(): string;

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract public function containers(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract public function widgets(): array;
}
