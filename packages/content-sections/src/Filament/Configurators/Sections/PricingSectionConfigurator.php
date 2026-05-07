<?php

declare(strict_types=1);

namespace Capell\ContentSections\Filament\Configurators\Sections;

class PricingSectionConfigurator extends PopularSectionConfigurator
{
    protected function sectionKey(): string
    {
        return 'pricing';
    }
}
