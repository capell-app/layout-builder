<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Filament\Configurators\BlockLibrary;

class AccordionContentBlockConfigurator extends PopularContentBlockConfigurator
{
    protected function blockKey(): string
    {
        return 'accordion';
    }

    protected function hasMainContentField(): bool
    {
        return false;
    }
}
