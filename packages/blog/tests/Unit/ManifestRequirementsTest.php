<?php

declare(strict_types=1);

describe('blog capell.json manifest', function (): void {
    $blogManifest = fn (): array => json_decode(
        file_get_contents(__DIR__ . '/../../capell.json'),
        associative: true,
    );

    $blogComposerManifest = fn (): array => json_decode(
        file_get_contents(__DIR__ . '/../../composer.json'),
        associative: true,
    );

    it('declares requires using full composer package names', function () use ($blogManifest): void {
        $manifest = $blogManifest();

        $requires = $manifest['dependencies']['requires'] ?? [];

        foreach ($requires as $requirement) {
            expect($requirement)->toContain('/');
        }
    });

    it('requires capell-app/core as a dependency', function () use ($blogManifest): void {
        $manifest = $blogManifest();

        expect($manifest['dependencies']['requires'])->toContain('capell-app/core');
    });

    it('does not require the removed layout-builder package', function () use ($blogManifest, $blogComposerManifest): void {
        $manifest = $blogManifest();
        $composerManifest = $blogComposerManifest();

        expect($manifest['dependencies']['requires'])
            ->not->toContain('capell-app/layout-builder')
            ->and($composerManifest['require'])
            ->not->toHaveKey('capell-app/layout-builder');
    });
});
