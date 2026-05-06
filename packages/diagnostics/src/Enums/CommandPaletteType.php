<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Enums;

enum CommandPaletteType: string
{
    case Navigation = 'navigation';
    case Artisan = 'artisan';
}
