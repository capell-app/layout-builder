<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Exceptions;

use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;
use RuntimeException;

final class MissingWidgetExtensionViewException extends RuntimeException
{
    public static function forDefinition(WidgetExtensionDefinitionData $definition): self
    {
        return new self(sprintf(
            'Widget extension [%s] from package [%s] has no active-theme view [%s] or valid package fallback view [%s].',
            $definition->key,
            $definition->packageName,
            $definition->themeView(),
            $definition->fallbackView,
        ));
    }
}
