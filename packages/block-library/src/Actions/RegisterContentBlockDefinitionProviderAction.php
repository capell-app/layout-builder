<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use Capell\BlockLibrary\Contracts\ContentBlockDefinitionProvider;
use Capell\BlockLibrary\Support\ContentBlockRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class RegisterContentBlockDefinitionProviderAction
{
    use AsObject;

    public function handle(ContentBlockRegistry $registry, ContentBlockDefinitionProvider $provider): void
    {
        foreach ($provider->definitions() as $definition) {
            $registry->register($definition);
        }
    }
}
