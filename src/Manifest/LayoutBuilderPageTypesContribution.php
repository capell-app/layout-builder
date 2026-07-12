<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Manifest;

use Capell\Core\Contracts\Extensions\ExtensionContribution;
use Capell\Core\Contracts\Extensions\RegistersExtensionPageType;

final class LayoutBuilderPageTypesContribution implements ExtensionContribution, RegistersExtensionPageType
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^1.0';
    }
}
