<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Configurators\Blocks\PageBlockAssetForm;
use InvalidArgumentException;

enum BlockAssetConfiguratorEnum: string
{
    case Page = PageBlockAssetForm::class;

    public static function fromName(string $name): self
    {
        throw_if($name === '' || $name === '0', InvalidArgumentException::class, 'BlockAssetConfiguratorEnum name cannot be empty');

        return constant(self::class . ('::' . $name))
            ?? throw new InvalidArgumentException('Invalid BlockAssetConfiguratorEnum name: ' . $name);
    }
}
