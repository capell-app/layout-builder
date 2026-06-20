<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum LayoutWidgetTarget: string
{
    case AdminFilament = 'admin-filament';
    case FrontendBlade = 'frontend-blade';
    case FrontendInertia = 'frontend-inertia';
    case FrontendLivewire = 'frontend-livewire';
}
