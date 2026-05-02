<?php

declare(strict_types=1);

describe('toolbar capell.json manifest', function (): void {
    $toolbarManifest = fn (): array => json_decode(
        file_get_contents(__DIR__ . '/../../capell.json'),
        associative: true,
    );

    it('declares requires using full composer package names', function () use ($toolbarManifest): void {
        $manifest = $toolbarManifest();

        foreach ($manifest['requires'] ?? [] as $requirement) {
            expect($requirement)->toContain('/');
        }
    });

    it('requires the Capell packages it depends on directly', function () use ($toolbarManifest): void {
        $manifest = $toolbarManifest();

        expect($manifest['requires'])->toContain('capell-app/core')
            ->and($manifest['requires'])->toContain('capell-app/frontend');
    });
});
