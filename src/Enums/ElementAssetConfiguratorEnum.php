<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Configurators\Elements\PageElementAssetForm;
use InvalidArgumentException;

enum ElementAssetConfiguratorEnum: string
{
    case Page = PageElementAssetForm::class;

    public static function fromName(string $name): self
    {
        throw_if($name === '' || $name === '0', InvalidArgumentException::class, 'ElementAssetConfiguratorEnum name cannot be empty');

        return constant(self::class . ('::' . $name))
            ?? throw new InvalidArgumentException('Invalid ElementAssetConfiguratorEnum name: ' . $name);
    }
}
