<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\Models\Widget;
use Filament\Resources\Resource;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Model;

enum LayoutTypeEnum: string implements HasLabel
{
    case Widget = 'widget';

    /**
     * @return class-string<resource>
     */
    public function getResource(): string
    {
        return match ($this) {
            self::Widget => WidgetResource::class,
        };
    }

    /**
     * @return class-string<Model>
     */
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

    public function getCreatorClass(): null
    {
        return null;
    }
}
