<?php

declare(strict_types=1);

namespace Capell\ContentSections\Filament\Configurators\Sections;

class FaqSectionConfigurator extends PopularSectionConfigurator
{
    protected function sectionKey(): string
    {
        return 'faq';
    }

    protected function hasMainContentField(): bool
    {
        return false;
    }
}
