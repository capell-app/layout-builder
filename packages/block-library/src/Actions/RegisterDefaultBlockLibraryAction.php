<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use Capell\BlockLibrary\Support\ContentBlockRegistry;
use Capell\BlockLibrary\Support\DefaultContentBlockDefinitionProvider;
use Lorisleiva\Actions\Concerns\AsObject;

class RegisterDefaultBlockLibraryAction
{
    use AsObject;

    public function handle(ContentBlockRegistry $registry): void
    {
        RegisterContentBlockDefinitionProviderAction::run(
            registry: $registry,
            provider: resolve(DefaultContentBlockDefinitionProvider::class),
        );
    }
}
