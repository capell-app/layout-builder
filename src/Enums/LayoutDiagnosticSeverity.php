<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum LayoutDiagnosticSeverity: string
{
    case Warning = 'warning';
    case Blocking = 'blocking';
}
