<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Manifest;

use Capell\Core\Contracts\Extensions\ExtensionContribution;

final class LayoutBuilderModelsContribution implements ExtensionContribution
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
