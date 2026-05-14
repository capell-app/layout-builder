<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Actions;

use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Support\BlockRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

final class ListBlockDefinitionsAction
{
    use AsObject;

    /**
     * @return array<string, BlockDefinitionData>
     */
    public function handle(): array
    {
        return resolve(BlockRegistry::class)->all();
    }
}
