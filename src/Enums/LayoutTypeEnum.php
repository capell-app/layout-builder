<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Resources\ContentResource;
use Capell\Layout\Filament\Resources\WidgetResource;

enum LayoutTypeEnum: string
{
    case Content = 'content';
    case Widget = 'widget';

    public function getResource(): string
    {
        return match ($this) {
            LayoutTypeEnum::Content => ContentResource::class,
            LayoutTypeEnum::Widget => WidgetResource::class,
        };
    }

    public function getTable(): string
    {
        return match ($this) {
            LayoutTypeEnum::Content => 'contents',
            LayoutTypeEnum::Widget => 'widgets',
        };
    }
}
