<?php

declare(strict_types=1);

namespace Capell\Assistant\Actions;

use Capell\Assistant\Data\AssistantRunData;
use Capell\Assistant\Support\AssistantModuleRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class RunAssistantCapabilityAction
{
    use AsObject;

    public function handle(AssistantRunData $run): mixed
    {
        $capability = resolve(AssistantModuleRegistry::class)
            ->capability($run->moduleKey, $run->capabilityKey);

        return $capability->actionClass::run($run);
    }
}
