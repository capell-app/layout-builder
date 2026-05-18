<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Configurators\Layouts\Blocks\DefaultLayoutBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Layouts\Blocks\PageLayoutBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Layouts\Blocks\ResultsLayoutBlockConfigurator;

enum LayoutBlockConfiguratorEnum: string
{
    case Default = DefaultLayoutBlockConfigurator::class;

    case Page = PageLayoutBlockConfigurator::class;

    case Results = ResultsLayoutBlockConfigurator::class;
}
