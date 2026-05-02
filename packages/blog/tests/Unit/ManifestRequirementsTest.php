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

        $requires = $manifest['requires'] ?? [];

        foreach ($requires as $requirement) {
            expect($requirement)->toContain('/');
        }
    });

    it('requires capell-app/core as a dependency', function () use ($blogManifest): void {
        $manifest = $blogManifest();

        expect($manifest['requires'])->toContain('capell-app/core');
    });

    it('requires mosaic in both package manifests', function () use ($blogManifest, $blogComposerManifest): void {
        $manifest = $blogManifest();
        $composerManifest = $blogComposerManifest();

        expect($manifest['requires'])
            ->toContain('capell-app/mosaic')
            ->and($composerManifest['require'])
            ->toHaveKey('capell-app/mosaic');
    });
});
