<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Resources\Contents\ContentResource;
use Capell\Layout\Filament\Resources\Widgets\WidgetResource;
use Capell\Layout\Support\Creator\TypeCreator;

enum LayoutTypeEnum: string
{
    case Content = 'content';

    case Widget = 'widget';

    public function getResource(): string
    {
        return match ($this) {
            self::Content => ContentResource::class,
            self::Widget => WidgetResource::class,
        };
    }

    public function getModel(): string
    {
        return match ($this) {
            self::Content => ModelEnum::Content->value,
            self::Widget => ModelEnum::Widget->value,
        };
    }

    public function getTable(): string
    {
        return match ($this) {
            self::Content => 'contents',
            self::Widget => 'widgets',
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
