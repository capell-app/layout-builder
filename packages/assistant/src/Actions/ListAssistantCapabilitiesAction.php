<?php

declare(strict_types=1);

namespace Capell\Assistant\Actions;

use Capell\Assistant\Data\AssistantCapabilityData;
use Capell\Assistant\Support\AssistantModuleRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class ListAssistantCapabilitiesAction
{
    use AsObject;

    /**
     * @return array<int, AssistantCapabilityData>
     */
    public function handle(): array
    {
        return resolve(AssistantModuleRegistry::class)->capabilities();
    }
}
