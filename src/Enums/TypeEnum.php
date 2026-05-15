<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Models\Element;

enum TypeEnum: string
{
    case Element = 'element';

    public function getModel(): string
    {
        return match ($this) {
            self::Element => Element::class,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Element => __('capell-layout-builder::generic.element')
        };
    }
}
