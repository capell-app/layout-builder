<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Contracts;

use Capell\BlockLibrary\Data\ContentBlockDefinitionData;

interface ContentBlockDefinitionProvider
{
    public const TAG = 'capell.block_library.definition_providers';

    /**
     * @return iterable<ContentBlockDefinitionData>
     */
    public function definitions(): iterable;
}
