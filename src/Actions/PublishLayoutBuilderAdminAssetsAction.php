<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Illuminate\Filesystem\Filesystem;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/**
 * @method static void run(?string $publicRoot = null)
 */
final class PublishLayoutBuilderAdminAssetsAction
{
    use AsFake;
    use AsObject;

    public function __construct(
        private readonly Filesystem $filesystem,
    ) {}

    public function handle(?string $publicRoot = null): void
    {
        $source = dirname(__DIR__, 2) . '/resources/css/layout-builder/admin/capell-layout-filament.css';
        $destination = ($publicRoot ?? public_path()) . '/css/capell-layout-builder/capell-layout-builder-filament.css';

        throw_unless($this->filesystem->isFile($source), RuntimeException::class, 'Layout Builder admin stylesheet source is missing.');

        $this->filesystem->ensureDirectoryExists(dirname($destination));
        throw_unless($this->filesystem->copy($source, $destination), RuntimeException::class, 'Unable to publish the Layout Builder admin stylesheet.');
    }
}
