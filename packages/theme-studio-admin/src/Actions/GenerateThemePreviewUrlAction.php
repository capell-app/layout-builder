<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Actions;

use Capell\ThemeStudio\Core\Preview\ThemePreviewSigner;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class GenerateThemePreviewUrlAction
{
    use AsObject;

    public function handle(string $themeKey, string $presetKey, ?string $path = null): string
    {
        $definition = resolve(ThemeRegistry::class)->definition($themeKey);
        $definition->presetOrFail($presetKey);

        $signer = resolve(ThemePreviewSigner::class);
        $previewPath = $path === null || $path === '' ? '/' : $path;
        $separator = str_contains($previewPath, '?') ? '&' : '?';

        return url($previewPath)
            . $separator
            . http_build_query([
                $signer->tokenParam() => $signer->generate($themeKey, $presetKey),
            ]);
    }
}
