<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Actions;

use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Support\BlockRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResolveBlockDefinitionAction
{
    use AsObject;

    public function handle(string $key): BlockDefinitionData
    {
        return resolve(BlockRegistry::class)->getOrFail($key);
    }
}
