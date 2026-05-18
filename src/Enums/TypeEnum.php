<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Models\Block;

enum TypeEnum: string
{
    case Block = 'block';

    public function getModel(): string
    {
        return match ($this) {
            self::Block => Block::class,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Block => __('capell-layout-builder::generic.block')
        };
    }
}
