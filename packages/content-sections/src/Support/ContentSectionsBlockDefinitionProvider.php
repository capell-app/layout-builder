<?php

declare(strict_types=1);

namespace Capell\ContentSections\Support;

use Capell\ContentBlocks\Contracts\BlockDefinitionProvider;
use Capell\ContentBlocks\Data\BlockDefinitionData;

final class ContentSectionsBlockDefinitionProvider implements BlockDefinitionProvider
{
    public function __construct(private readonly SectionRegistry $sections) {}

    /**
     * @return iterable<BlockDefinitionData>
     */
    public function definitions(): iterable
    {
        foreach ($this->sections->all() as $section) {
            yield new BlockDefinitionData(
                key: 'section.' . $section->key,
                label: $section->label,
                description: $section->description,
                category: $section->group,
                view: $section->component,
                defaults: $section->defaults,
                safeForPublicOutput: true,
                sourcePackage: 'content-sections',
            );
        }
    }
}
