<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ActionLinkEnum: string implements HasIcon, HasLabel
{
    case Link = 'link';

    case Page = 'page';

    case VideoPopup = 'video_popup';

    public function getLabel(): string
    {
        return match ($this) {
            self::Link => __('capell-admin::generic.link'),
            self::Page => __('capell-admin::generic.page'),
            self::VideoPopup => __('capell-layout-builder::generic.video_popup'),
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Link => 'heroicon-o-link',
            self::Page => 'heroicon-o-document-text',
            self::VideoPopup => 'heroicon-o-play-circle',
        };
    }
}
