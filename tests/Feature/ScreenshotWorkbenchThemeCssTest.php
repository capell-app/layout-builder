<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

it('compiles utilities used by new package views into the screenshot workbench asset', function (): void {
    $repository = dirname(__DIR__, 4);
    $fixture = sys_get_temp_dir() . '/screenshot-css-' . bin2hex(random_bytes(8));
    $appRoot = $fixture . '/screenshot-app';
    File::ensureDirectoryExists($fixture . '/scripts/screenshots');
    File::ensureDirectoryExists($fixture . '/vendor/filament/filament/resources/css');
    File::put($fixture . '/vendor/filament/filament/resources/css/theme.css', '');
    File::copy($repository . '/scripts/screenshots/build-filament-theme-css.mjs', $fixture . '/scripts/screenshots/build-filament-theme-css.mjs');
    symlink($repository . '/node_modules', $fixture . '/node_modules');

    try {
        foreach (['first-package' => 'bg-fuchsia-700', 'later-package' => 'gap-11'] as $package => $utility) {
            $views = $fixture . '/packages/' . $package . '/resources/views/nested';
            File::ensureDirectoryExists($views);
            File::put($views . '/card.blade.php', '<div class="' . $utility . '">Customer card</div>');
            $process = new Process(['node', 'scripts/screenshots/build-filament-theme-css.mjs'], $fixture, [
                'CAPELL_SCREENSHOT_APP_PATH' => $appRoot,
            ]);
            $process->setTimeout(30);
            $process->run();
            expect($process->getExitCode())->toBe(0, $process->getOutput() . $process->getErrorOutput());
            $build = $appRoot . '/public/build/filament';
            $manifest = capell_json_file_array($build . '/manifest.json');
            $entry = $manifest['resources/css/filament/admin/theme.css'] ?? null;
            expect(data_get($entry, 'isEntry'))->toBeTrue();
            // The compiled asset must style both existing and newly installed package views.
            $file = data_get($entry, 'file');
            if (! is_string($file) || $file === '') {
                throw new RuntimeException('Expected the stylesheet manifest to resolve a file.');
            }
            expect(File::get($build . '/' . $file))->toContain('.' . $utility);
        }
    } finally {
        unlink($fixture . '/node_modules');
        File::deleteDirectory($fixture);
    }
});
