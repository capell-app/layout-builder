<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\PublishLayoutBuilderAdminAssetsAction;
use Illuminate\Filesystem\Filesystem;

it('publishes the registered admin stylesheet during package installation', function (): void {
    $publicRoot = sys_get_temp_dir() . '/capell-layout-builder-assets-' . bin2hex(random_bytes(6));
    $filesystem = new Filesystem;

    try {
        PublishLayoutBuilderAdminAssetsAction::run($publicRoot);

        $publishedPath = $publicRoot . '/css/capell-layout-builder/capell-layout-builder-filament.css';
        $sourcePath = dirname(__DIR__, 3) . '/resources/css/layout-builder/admin/capell-layout-filament.css';

        expect($publishedPath)->toBeFile()
            ->and(hash_file('sha256', $publishedPath))->toBe(hash_file('sha256', $sourcePath));
    } finally {
        $filesystem->deleteDirectory($publicRoot);
    }
});
