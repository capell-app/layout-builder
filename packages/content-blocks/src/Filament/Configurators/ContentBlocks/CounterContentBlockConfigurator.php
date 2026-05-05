<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Filament\Configurators\ContentBlocks;

class CounterContentBlockConfigurator extends PopularContentBlockConfigurator
{
    protected function blockKey(): string
    {
        return 'counter';
    }
}
