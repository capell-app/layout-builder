<?php

declare(strict_types=1);

namespace Capell\ContentSections\Actions;

use Capell\ContentSections\Support\DefaultSectionDefinitionProvider;
use Capell\ContentSections\Support\SectionRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class RegisterDefaultSectionsAction
{
    use AsObject;

    public function handle(SectionRegistry $registry): void
    {
        RegisterSectionDefinitionProviderAction::run(
            registry: $registry,
            provider: resolve(DefaultSectionDefinitionProvider::class),
        );
    }
}
