<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Filament\Configurators\ContentBlocks;

class PricingContentBlockConfigurator extends PopularContentBlockConfigurator
{
    protected function blockKey(): string
    {
        return 'pricing';
    }
}
