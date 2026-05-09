<?php

declare(strict_types=1);

it('requires the vite plugin version that supports plugin fonts', function (): void {
    $dependencies = require __DIR__ . '/../../config/capell-foundation-theme.php';

    expect($dependencies['npm_dependencies'])
        ->toHaveKey('fontaine', '^0.5.0')
        ->toHaveKey('laravel-vite-plugin', '^3.1.0')
        ->toHaveKey('vite', '^8.0')
        ->not->toHaveKey('@tailwindcss/form-builder');
});
