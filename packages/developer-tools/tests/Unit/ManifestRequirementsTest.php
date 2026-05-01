<?php

declare(strict_types=1);

use Capell\DeveloperTools\Providers\AdminServiceProvider;
use Capell\DeveloperTools\Providers\DeveloperToolsServiceProvider;

describe('developer-tools capell.json manifest', function (): void {
    it('declares admin and console package metadata', function (): void {
        $manifest = json_decode(
            file_get_contents(__DIR__ . '/../../capell.json'),
            associative: true,
        );

        expect($manifest)
            ->toMatchArray([
                'name' => 'capell-app/developer-tools',
                'kind' => 'package',
                'capell-version' => '^4.0',
            ])
            ->and($manifest['contexts'])->toContain('admin')
            ->and($manifest['providers']['shared'])->toContain(DeveloperToolsServiceProvider::class)
            ->and($manifest['providers']['admin'])->toContain(AdminServiceProvider::class);
    });
});
