<?php

declare(strict_types=1);

it('keeps layout builder notification titles and bodies translated', function (): void {
    $files = [
        __DIR__ . '/../../src/Livewire/Filament/LayoutBuilder.php',
        __DIR__ . '/../../src/Livewire/Filament/Support/LayoutBuilderActionFactory.php',
        __DIR__ . '/../../src/Livewire/Filament/ModalTableSelect.php',
        __DIR__ . '/../../src/Livewire/Filament/Concerns/ManagesLayoutBuilderState.php',
        __DIR__ . '/../../src/Filament/Components/Forms/AssetsRepeater.php',
        __DIR__ . '/../../src/Filament/Components/Forms/WidgetSelect.php',
        __DIR__ . '/../../src/Listeners/SiteTreeRebuilt.php',
    ];

    foreach ($files as $file) {
        $contents = file_get_contents($file);
        expect($contents)->toBeString();

        assert(is_string($contents));

        expect($contents)
            ->not->toMatch('/->(?:title|body)\(\s*[\'"][^\'"]+[\'"]\s*\)/')
            ->not->toMatch('/->body\([^)]*getMessage\(\)/');
    }
});
