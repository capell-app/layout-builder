<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Enums;

enum CommandPaletteDanger: string
{
    case Safe = 'safe';
    case Confirm = 'confirm';
    case Dangerous = 'dangerous';
}
