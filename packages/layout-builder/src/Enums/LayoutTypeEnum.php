<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use Filament\Support\Contracts\HasLabel;

enum LayoutTypeEnum: string implements HasLabel
{
    case Widget = 'widget';

    public function getResource(): string
    {
        return match ($this) {
            self::Widget => WidgetResource::class,
        };
    }

    public function getModel(): string
    {
        return match ($this) {
            self::Widget => Widget::class,
        };
    }

    public function getTable(): string
    {
        return match ($this) {
            self::Widget => 'widgets',
        };
    }

    // TODO when this is translated this causes Livewire error: Exception: Property type not supported in Livewire for property: [{}]
    public function getLabel(): string
    {
        return match ($this) {
            self::Widget => 'Widget',
        };
    }

    /**
     * @return class-string<TypeCreator>|null
     */
    public function getCreatorClass(): ?string
    {
        return TypeCreator::class;
    }
}
