<?php

declare(strict_types=1);

namespace Capell\ContentSections\Filament\Configurators\Sections;

class AccordionSectionConfigurator extends PopularSectionConfigurator
{
    protected function sectionKey(): string
    {
        return 'accordion';
    }

    protected function hasMainContentField(): bool
    {
        return false;
    }
}
