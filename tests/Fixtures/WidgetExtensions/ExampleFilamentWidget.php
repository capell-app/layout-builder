<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Capell\Admin\Contracts\Widgets\FilamentWidget;
use Filament\Forms\Components\Builder\Block;

final class ExampleFilamentWidget implements FilamentWidget
{
    public static function getWidgetName(): string
    {
        return 'capell-app.slideshow';
    }

    public static function make(): Block
    {
        return Block::make(self::getWidgetName());
    }
}
