<?php

declare(strict_types=1);

namespace Capell\ContentSections\Actions;

use Capell\ContentSections\Contracts\SectionDefinitionProvider;
use Capell\ContentSections\Support\SectionRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class RegisterSectionDefinitionProviderAction
{
    use AsObject;

    public function handle(SectionRegistry $registry, SectionDefinitionProvider $provider): void
    {
        foreach ($provider->definitions() as $definition) {
            $registry->register($definition);
        }
    }
}
