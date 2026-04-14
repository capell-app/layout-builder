<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ActionLinkEnum: string implements HasIcon, HasLabel
{
    case Link = 'link';

    case Page = 'page';

    public function getLabel(): string
    {
        return match ($this) {
            self::Link => __('capell-admin::generic.link'),
            self::Page => __('capell-admin::generic.page'),
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Link => 'heroicon-o-link',
            self::Page => 'heroicon-o-document-text',
        };
    }
}
