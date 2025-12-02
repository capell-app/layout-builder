<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

enum ContainerWidthEnum: string
{
    case Default = 'container';

    case Full = 'full';

    case Small = 'sm';

    case Medium = 'md';

    case Large = 'lg';

    case ExtraLarge = 'xl';
}
