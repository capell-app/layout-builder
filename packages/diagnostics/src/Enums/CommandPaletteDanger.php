<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Enums;

enum CommandPaletteDanger: string
{
    case Safe = 'safe';
    case Confirm = 'confirm';
    case Dangerous = 'dangerous';
}
