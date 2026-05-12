<?php

declare(strict_types=1);

describe('address capell.json manifest', function (): void {
    it('declares requires using full composer package names', function (): void {
        $manifest = json_decode(
            file_get_contents(__DIR__ . '/../../capell.json'),
            associative: true,
        );

        $requires = $manifest['dependencies']['requires'] ?? [];

        foreach ($requires as $requirement) {
            expect($requirement)->toContain('/');
        }
    });

    it('requires capell-app/admin as a dependency', function (): void {
        $manifest = json_decode(
            file_get_contents(__DIR__ . '/../../capell.json'),
            associative: true,
        );

        expect($manifest['dependencies']['requires'])->toContain('capell-app/admin');
    });

    it('declares its demo command for package demo installs', function (): void {
        $manifest = json_decode(
            file_get_contents(__DIR__ . '/../../capell.json'),
            associative: true,
        );

        expect($manifest['commands']['demo'])->toBe('capell:address-demo');
    });

    it('keeps composer package requirements aligned with the manifest', function (): void {
        $manifest = json_decode(
            file_get_contents(__DIR__ . '/../../capell.json'),
            associative: true,
        );
        $composer = json_decode(
            file_get_contents(__DIR__ . '/../../composer.json'),
            associative: true,
        );

        $composerPackageRequirements = array_values(array_filter(
            array_keys($composer['require'] ?? []),
            fn (string $packageName): bool => str_starts_with($packageName, 'capell-app/'),
        ));

        sort($composerPackageRequirements);

        $manifestRequirements = $manifest['dependencies']['requires'] ?? [];
        sort($manifestRequirements);

        expect($composerPackageRequirements)->toBe($manifestRequirements);
    });
});
