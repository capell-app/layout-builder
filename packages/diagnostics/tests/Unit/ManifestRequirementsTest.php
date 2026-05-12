<?php

declare(strict_types=1);

use Capell\Diagnostics\Providers\AdminServiceProvider;
use Capell\Diagnostics\Providers\DiagnosticsServiceProvider;

describe('diagnostics capell.json manifest', function (): void {
    it('declares admin and console package metadata', function (): void {
        $manifest = json_decode(
            file_get_contents(__DIR__ . '/../../capell.json'),
            associative: true,
        );

        expect($manifest)
            ->toMatchArray([
                'name' => 'capell-app/diagnostics',
                'kind' => 'package',
                'capellApiVersion' => '^4.0',
            ])
            ->and($manifest['surfaces'])->toContain('admin')
            ->and($manifest['providers']['runtime'])->toContain(DiagnosticsServiceProvider::class)
            ->and($manifest['providers']['admin'])->toContain(AdminServiceProvider::class);
    });
});
