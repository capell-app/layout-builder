<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\Assets\ContentWidgetAssetForm;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\Assets\PageWidgetAssetForm;
use InvalidArgumentException;

enum WidgetAssetSchemaEnum: string
{
    case Content = ContentWidgetAssetForm::class;
    case Page = PageWidgetAssetForm::class;

    public static function fromName(string $name): self
    {
        return constant(self::class . ('::' . $name))
            ?? throw new InvalidArgumentException('Invalid WidgetAssetSchemaEnum name: ' . $name);
    }
}
