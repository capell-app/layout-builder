<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Configurators\Layouts\DefaultLayoutContainerConfigurator;

enum LayoutContainerConfiguratorEnum: string
{
    case Default = DefaultLayoutContainerConfigurator::class;
}
