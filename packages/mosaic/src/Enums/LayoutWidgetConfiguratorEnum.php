<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Configurators\Layouts\Widgets\DefaultLayoutWidgetConfigurator;
use Capell\Mosaic\Filament\Configurators\Layouts\Widgets\PageLayoutWidgetConfigurator;
use Capell\Mosaic\Filament\Configurators\Layouts\Widgets\ResultsLayoutWidgetConfigurator;

enum LayoutWidgetConfiguratorEnum: string
{
    case Default = DefaultLayoutWidgetConfigurator::class;

    case Page = PageLayoutWidgetConfigurator::class;

    case Results = ResultsLayoutWidgetConfigurator::class;
}
