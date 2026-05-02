<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Configurators\Languages;

use Capell\Address\Filament\Components\Forms\FlagSelect;
use Capell\Admin\Filament\Configurators\Languages\DefaultLanguageConfigurator as AdminDefaultLanguageConfigurator;
use Filament\Forms\Components\Field;

class DefaultLanguageConfigurator extends AdminDefaultLanguageConfigurator
{
    protected function makeFlagField(): Field
    {
        return FlagSelect::make('flag');
    }
}
