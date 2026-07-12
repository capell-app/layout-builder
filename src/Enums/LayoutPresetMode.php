<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum LayoutPresetMode: string
{
    case Copy = 'copy';
    case Linked = 'linked';
}
