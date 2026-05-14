<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Contracts;

use Capell\ContentBlocks\Data\BlockDefinitionData;
use Illuminate\Contracts\Support\Htmlable;

interface BlockRenderer
{
    /**
     * @param  array<string, mixed>  $state
     */
    public function render(BlockDefinitionData $definition, array $state = []): Htmlable|string;
}
