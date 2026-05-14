<?php

declare(strict_types=1);

use Capell\ContentBlocks\Providers\ContentBlocksServiceProvider;

describe('content-blocks capell.json manifest', function (): void {
    it('declares the foundation package metadata and provider', function (): void {
        $manifest = json_decode(
            file_get_contents(__DIR__ . '/../../capell.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        expect($manifest)
            ->toMatchArray([
                'name' => 'capell-app/content-blocks',
                'slug' => 'content-blocks',
                'kind' => 'package',
                'capellApiVersion' => '^4.0',
                'product' => [
                    'group' => 'Capell Foundation',
                    'tier' => 'free',
                    'bundle' => 'foundation',
                ],
            ])
            ->and($manifest['surfaces'])->toContain('shared')
            ->and($manifest['providers']['runtime'])->toContain(ContentBlocksServiceProvider::class);
    });
});
