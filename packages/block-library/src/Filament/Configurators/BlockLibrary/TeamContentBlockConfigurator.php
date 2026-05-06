<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Filament\Configurators\BlockLibrary;

class TeamContentBlockConfigurator extends PopularContentBlockConfigurator
{
    protected function blockKey(): string
    {
        return 'team';
    }
}
