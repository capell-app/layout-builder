<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ContainerAlignmentEnum: string implements HasLabel
{
    case Start = 'start';

    case Center = 'center';

    case End = 'end';

    case Stretch = 'stretch';

    public function getLabel(): string
    {
        return match ($this) {
            self::Start => __('capell-layout-builder::form.alignment_start'),
            self::Center => __('capell-layout-builder::form.alignment_center'),
            self::End => __('capell-layout-builder::form.alignment_end'),
            self::Stretch => __('capell-layout-builder::form.alignment_stretch'),
        };
    }
}
