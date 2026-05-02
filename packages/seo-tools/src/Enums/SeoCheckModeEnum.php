<?php

declare(strict_types=1);

namespace Capell\SeoTools\Enums;

use Filament\Support\Contracts\HasLabel;

enum SeoCheckModeEnum: string implements HasLabel
{
    case Blocker = 'blocker';
    case Warning = 'warning';
    case Ignored = 'ignored';

    public function getLabel(): string
    {
        return __('capell-seo-tools::generic.seo_check_mode_' . $this->value);
    }
}
