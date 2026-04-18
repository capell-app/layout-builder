<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Resources\Sections\SectionResource;
use Capell\Mosaic\Filament\Resources\Widgets\WidgetResource;
use Capell\Mosaic\Support\Creator\TypeCreator;
use Filament\Support\Contracts\HasLabel;

enum LayoutTypeEnum: string implements HasLabel
{
    case Content = 'content';

    case Widget = 'widget';

    public function getResource(): string
    {
        return match ($this) {
            self::Content => SectionResource::class,
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
            self::Content => 'sections',
            self::Widget => 'widgets',
        };
    }

    // TODO when this is translated this causes Livewire error: Exception: Property type not supported in Livewire for property: [{}]
    public function getLabel(): string
    {
        return match ($this) {
            self::Content => 'Content',
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
