<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Filament\Configurators\Widgets\PageWidgetAssetForm;
use Capell\Mosaic\Filament\Configurators\Widgets\SectionWidgetAssetForm;
use InvalidArgumentException;

enum WidgetAssetConfiguratorEnum: string
{
    case Section = SectionWidgetAssetForm::class;

    case Page = PageWidgetAssetForm::class;

    public static function fromName(string $name): self
    {
        throw_if($name === '' || $name === '0', InvalidArgumentException::class, 'WidgetAssetConfiguratorEnum name cannot be empty');

        return constant(self::class . ('::' . $name))
            ?? throw new InvalidArgumentException('Invalid WidgetAssetConfiguratorEnum name: ' . $name);
    }
}
