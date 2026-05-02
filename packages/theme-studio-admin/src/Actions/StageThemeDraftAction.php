<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Actions;

use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class StageThemeDraftAction
{
    use AsObject;

    public function handle(string $themeKey, string $presetKey): ThemeStudioSettings
    {
        $definition = resolve(ThemeRegistry::class)->definition($themeKey);
        $definition->presetOrFail($presetKey);

        $settings = resolve(ThemeStudioSettings::class);
        $settings->draftTheme = $themeKey;
        $settings->draftPreset = $presetKey;
        $settings->draftWorkspaceId = null;
        $settings->save();

        return $settings;
    }
}
