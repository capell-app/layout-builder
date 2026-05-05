<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Filament\Configurators\ContentBlocks;

class FaqContentBlockConfigurator extends PopularContentBlockConfigurator
{
    protected function blockKey(): string
    {
        return 'faq';
    }

    protected function hasMainContentField(): bool
    {
        return false;
    }
}
