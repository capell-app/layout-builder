<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Manifest;

use Capell\Core\Contracts\Extensions\ExtensionContribution;
use Capell\Core\Contracts\Extensions\RegistersExtensionRoute;

final class LayoutBuilderRoutesContribution implements ExtensionContribution, RegistersExtensionRoute
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.1';
    }
}
