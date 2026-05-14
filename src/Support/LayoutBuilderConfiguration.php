<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

final class LayoutBuilderConfiguration
{
    public static function defaultEditorMode(): string
    {
        $configuredMode = config('capell-layout-builder.editor_mode.default');

        if (is_string($configuredMode) && $configuredMode !== '') {
            return $configuredMode;
        }

        $legacyMode = config('capell-layout-builder.editor_mode');

        if (is_string($legacyMode) && $legacyMode !== '') {
            return $legacyMode;
        }

        $adminMode = config('capell-admin.layout_builder.default_editor_mode');

        return is_string($adminMode) && $adminMode !== '' ? $adminMode : 'content_first';
    }

    /**
     * @return array<int, string>
     */
    public static function allowedEditorModes(): array
    {
        $configuredModes = config('capell-layout-builder.editor_mode.allowed');

        if (is_array($configuredModes) && $configuredModes !== []) {
            return array_values(array_filter($configuredModes, 'is_string'));
        }

        $adminModes = config('capell-admin.layout_builder.allowed_editor_modes');

        if (is_array($adminModes) && $adminModes !== []) {
            return array_values(array_filter($adminModes, 'is_string'));
        }

        return ['content_first', 'layout_first'];
    }

    public static function matchFrontendContainerLayout(): bool
    {
        return (bool) config(
            'capell-layout-builder.preview.match_frontend_container_layout',
            config('capell-admin.layout_builder.preview.match_frontend_container_layout', true),
        );
    }

    public static function lazy(): bool
    {
        return (bool) config('capell-layout-builder.layout_builder.lazy', true);
    }
}
