<?php

declare(strict_types=1);

describe('blog capell.json manifest', function (): void {
    it('declares dependsOn using full composer package names', function (): void {
        $manifest = json_decode(
            file_get_contents(__DIR__ . '/../../../../packages/blog/capell.json'),
            associative: true,
        );

        $dependsOn = $manifest['dependsOn'] ?? [];

        foreach ($dependsOn as $requirement) {
            expect($requirement)->toContain('/');
        }
    });

    it('requires capell-app/mosaic as a dependency', function (): void {
        $manifest = json_decode(
            file_get_contents(__DIR__ . '/../../../../packages/blog/capell.json'),
            associative: true,
        );

        expect($manifest['dependsOn'])->toContain('capell-app/mosaic');
    });
});
