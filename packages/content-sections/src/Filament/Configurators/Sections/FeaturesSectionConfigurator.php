<?php

declare(strict_types=1);

namespace Capell\ContentSections\Filament\Configurators\Sections;

class FeaturesSectionConfigurator extends PopularSectionConfigurator
{
    protected function sectionKey(): string
    {
        return 'features';
    }
}
