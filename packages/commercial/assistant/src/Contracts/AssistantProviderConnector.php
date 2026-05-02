<?php

declare(strict_types=1);

namespace Capell\Assistant\Contracts;

use Capell\Assistant\Data\AssistantRunData;

interface AssistantProviderConnector
{
    public function key(): string;

    public function label(): string;

    /**
     * @return array<string, mixed>
     */
    public function complete(AssistantRunData $run): array;
}
