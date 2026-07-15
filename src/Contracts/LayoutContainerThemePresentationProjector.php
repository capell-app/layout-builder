<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts;

use Capell\LayoutBuilder\Data\LayoutContainerThemePresentationData;

interface LayoutContainerThemePresentationProjector
{
    public const string TAG = 'capell.layout_builder.layout_container_theme_presentation_projectors';

    public function themeKey(): string;

    /**
     * @param  array<string, mixed>  $state
     */
    public function project(array $state): LayoutContainerThemePresentationData;
}
