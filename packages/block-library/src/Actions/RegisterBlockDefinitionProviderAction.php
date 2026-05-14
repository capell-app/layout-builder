<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Actions;

use Capell\ContentBlocks\Contracts\BlockDefinitionProvider;
use Capell\ContentBlocks\Support\BlockRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

final class RegisterBlockDefinitionProviderAction
{
    use AsObject;

    public function handle(BlockRegistry $registry, BlockDefinitionProvider $provider): void
    {
        foreach ($provider->definitions() as $definition) {
            $registry->register($definition);
        }
    }
}
