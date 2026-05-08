<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Actions;

use Capell\FrontendOptimizer\Data\RenderProfileData;
use Illuminate\Contracts\Filesystem\Factory;
use Lorisleiva\Actions\Concerns\AsAction;

class StoreRenderProfileManifestAction
{
    use AsAction;

    public function __construct(private readonly Factory $filesystems) {}

    public function handle(RenderProfileData $profile): string
    {
        $path = $this->manifestPath($profile);

        $contents = json_encode($profile->manifest(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $this->filesystems->disk('local')->put($path, $contents . PHP_EOL);

        return $path;
    }

    private function manifestPath(RenderProfileData $profile): string
    {
        $directory = trim($this->configString('capell-frontend-optimizer.paths.manifests', 'capell/frontend-optimizer/manifests'), '/');

        return sprintf('%s/%s.json', $directory, $profile->hash);
    }

    private function configString(string $key, string $default): string
    {
        return config($key, $default);
    }
}
