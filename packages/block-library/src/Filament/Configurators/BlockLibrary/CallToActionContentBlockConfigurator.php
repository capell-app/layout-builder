<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Filament\Configurators\BlockLibrary;

class CallToActionContentBlockConfigurator extends PopularContentBlockConfigurator
{
    protected function blockKey(): string
    {
        return 'call_to_action';
    }
}
