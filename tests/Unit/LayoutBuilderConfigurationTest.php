<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Support\LayoutBuilderConfiguration;

it('resolves package owned editor mode configuration', function (): void {
    config()->set('capell-layout-builder.editor_mode.default', 'layout_first');
    config()->set('capell-layout-builder.editor_mode.allowed', ['layout_first']);
    config()->set('capell-admin.layout_builder.default_editor_mode', 'content_first');
    config()->set('capell-admin.layout_builder.allowed_editor_modes', ['content_first']);

    expect(LayoutBuilderConfiguration::defaultEditorMode())->toBe('layout_first')
        ->and(LayoutBuilderConfiguration::allowedEditorModes())->toBe(['layout_first']);
});

it('falls back to legacy admin editor mode configuration', function (): void {
    config()->set('capell-layout-builder.editor_mode.default', null);
    config()->set('capell-layout-builder.editor_mode.allowed', []);
    config()->set('capell-admin.layout_builder.default_editor_mode', 'layout_first');
    config()->set('capell-admin.layout_builder.allowed_editor_modes', ['layout_first']);

    expect(LayoutBuilderConfiguration::defaultEditorMode())->toBe('layout_first')
        ->and(LayoutBuilderConfiguration::allowedEditorModes())->toBe(['layout_first']);
});

it('resolves package owned preview configuration', function (): void {
    config()->set('capell-layout-builder.preview.match_frontend_container_layout', false);
    config()->set('capell-admin.layout_builder.preview.match_frontend_container_layout', true);

    expect(LayoutBuilderConfiguration::matchFrontendContainerLayout())->toBeFalse();
});
