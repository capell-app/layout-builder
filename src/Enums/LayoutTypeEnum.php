<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\Core\LayoutBuilder\Support\Creator\TypeCreator;
use Capell\Core\Models\Widget;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
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
