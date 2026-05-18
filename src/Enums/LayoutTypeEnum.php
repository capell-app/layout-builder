<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Resources\Blocks\BlockResource;
use Capell\LayoutBuilder\Models\Block;
use Filament\Support\Contracts\HasLabel;

enum LayoutTypeEnum: string implements HasLabel
{
    case Block = 'block';

    public function getResource(): string
    {
        return match ($this) {
            self::Block => BlockResource::class,
        };
    }

    public function getModel(): string
    {
        return match ($this) {
            self::Block => Block::class,
        };
    }

    public function getTable(): string
    {
        return match ($this) {
            self::Block => 'blocks',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Block => 'Block',
        };
    }

    public function getCreatorClass(): ?string
    {
        return null;
    }
}
