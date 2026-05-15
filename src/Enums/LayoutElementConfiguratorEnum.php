<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Configurators\Layouts\Elements\DefaultLayoutElementConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Layouts\Elements\PageLayoutElementConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Layouts\Elements\ResultsLayoutElementConfigurator;

enum LayoutElementConfiguratorEnum: string
{
    case Default = DefaultLayoutElementConfigurator::class;

    case Page = PageLayoutElementConfigurator::class;

    case Results = ResultsLayoutElementConfigurator::class;
}
