<?php

declare(strict_types=1);

namespace Capell\ContentSections\Contracts;

use Capell\ContentSections\Data\SectionDefinitionData;

interface SectionDefinitionProvider
{
    public const TAG = 'capell.content_sections.definition_providers';

    /**
     * @return iterable<SectionDefinitionData>
     */
    public function definitions(): iterable;
}
