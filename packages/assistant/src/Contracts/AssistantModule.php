<?php

declare(strict_types=1);

namespace Capell\Assistant\Contracts;

use Capell\Assistant\Data\AssistantCapabilityData;

interface AssistantModule
{
    public function key(): string;

    public function label(): string;

    /**
     * @return array<int, AssistantCapabilityData>
     */
    public function capabilities(): array;
}
