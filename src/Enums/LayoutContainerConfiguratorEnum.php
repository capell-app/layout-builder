<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Configurators\Layouts\DefaultLayoutContainerConfigurator;

enum LayoutContainerConfiguratorEnum: string
{
    case Default = DefaultLayoutContainerConfigurator::class;
}
