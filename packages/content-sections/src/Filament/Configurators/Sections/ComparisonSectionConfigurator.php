<?php

declare(strict_types=1);

namespace Capell\ContentSections\Filament\Configurators\Sections;

class ComparisonSectionConfigurator extends PopularSectionConfigurator
{
    protected function sectionKey(): string
    {
        return 'comparison';
    }
}
