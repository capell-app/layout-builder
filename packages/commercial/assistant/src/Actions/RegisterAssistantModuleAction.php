<?php

declare(strict_types=1);

namespace Capell\Assistant\Actions;

use Capell\Assistant\Contracts\AssistantModule;
use Capell\Assistant\Support\AssistantModuleRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class RegisterAssistantModuleAction
{
    use AsObject;

    public function handle(AssistantModule $module): void
    {
        resolve(AssistantModuleRegistry::class)->register($module);
    }
}
