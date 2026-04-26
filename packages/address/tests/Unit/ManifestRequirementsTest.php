<?php

declare(strict_types=1);

describe('address capell.json manifest', function (): void {
    it('declares requires using full composer package names', function (): void {
        $manifest = json_decode(
            file_get_contents(__DIR__ . '/../../../../packages/address/capell.json'),
            associative: true,
        );

        $requires = $manifest['requires'] ?? [];

        foreach ($requires as $requirement) {
            expect($requirement)->toContain('/');
        }
    });

    it('requires capell-app/admin as a dependency', function (): void {
        $manifest = json_decode(
            file_get_contents(__DIR__ . '/../../../../packages/address/capell.json'),
            associative: true,
        );

        expect($manifest['requires'])->toContain('capell-app/admin');
    });
});
