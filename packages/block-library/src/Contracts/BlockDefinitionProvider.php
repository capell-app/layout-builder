<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Contracts;

use Capell\ContentBlocks\Data\BlockDefinitionData;

interface BlockDefinitionProvider
{
    public const TAG = 'capell.content_blocks.definition_providers';

    /**
     * @return iterable<BlockDefinitionData>
     */
    public function definitions(): iterable;
}
