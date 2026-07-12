<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Configurators\Widgets\PageWidgetAssetForm;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\RegisteredAssetWidgetAssetForm;
use InvalidArgumentException;

enum WidgetAssetConfiguratorEnum: string
{
    case Page = PageWidgetAssetForm::class;

    case RegisteredAsset = RegisteredAssetWidgetAssetForm::class;

    public static function fromName(string $name): self
    {
        throw_if($name === '' || $name === '0', InvalidArgumentException::class, 'WidgetAssetConfiguratorEnum name cannot be empty');

        return constant(self::class . ('::' . $name))
            ?? throw new InvalidArgumentException('Invalid WidgetAssetConfiguratorEnum name: ' . $name);
    }
}
