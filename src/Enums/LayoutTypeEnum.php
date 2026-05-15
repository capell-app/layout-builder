<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Resources\Elements\ElementResource;
use Capell\LayoutBuilder\Models\Element;
use Filament\Support\Contracts\HasLabel;

enum LayoutTypeEnum: string implements HasLabel
{
    case Element = 'element';

    public function getResource(): string
    {
        return match ($this) {
            self::Element => ElementResource::class,
        };
    }

    public function getModel(): string
    {
        return match ($this) {
            self::Element => Element::class,
        };
    }

    public function getTable(): string
    {
        return match ($this) {
            self::Element => 'elements',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Element => 'Element',
        };
    }

    public function getCreatorClass(): ?string
    {
        return null;
    }
}
