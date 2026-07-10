<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetExtensions;

use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;
use Capell\LayoutBuilder\Exceptions\MissingWidgetExtensionViewException;
use Illuminate\Contracts\View\Factory;

final readonly class WidgetExtensionViewResolver
{
    public function __construct(
        private Factory $views,
    ) {}

    public function resolve(WidgetExtensionDefinitionData $definition): string
    {
        if (! $this->views->exists($definition->fallbackView)) {
            throw MissingWidgetExtensionViewException::forDefinition($definition);
        }

        $themeView = $definition->themeView();

        if ($this->views->exists($themeView)) {
            return $themeView;
        }

        return $definition->fallbackView;
    }
}
