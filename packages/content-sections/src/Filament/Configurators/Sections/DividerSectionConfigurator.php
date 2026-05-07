<?php

declare(strict_types=1);

namespace Capell\ContentSections\Filament\Configurators\Sections;

class DividerSectionConfigurator extends PopularSectionConfigurator
{
    protected function sectionKey(): string
    {
        return 'divider';
    }

    protected function hasMainContentField(): bool
    {
        return false;
    }
}
