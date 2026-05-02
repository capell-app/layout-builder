<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Actions;

use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class ActivateApprovedThemeDraftAction
{
    use AsObject;

    public function handle(int $workspaceId): ThemeStudioSettings
    {
        $settings = resolve(ThemeStudioSettings::class);

        if ($settings->draftWorkspaceId !== $workspaceId) {
            return $settings;
        }

        if ($settings->draftTheme === null || $settings->draftPreset === null) {
            return $settings;
        }

        resolve(ThemeRegistry::class)
            ->definition($settings->draftTheme)
            ->presetOrFail($settings->draftPreset);

        $settings->activeTheme = $settings->draftTheme;
        $settings->activePreset = $settings->draftPreset;
        $settings->draftTheme = null;
        $settings->draftPreset = null;
        $settings->draftWorkspaceId = null;
        $settings->save();

        return $settings;
    }
}
