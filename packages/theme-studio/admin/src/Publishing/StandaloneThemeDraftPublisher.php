<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Publishing;

use Capell\ThemeStudio\Admin\Contracts\ThemeDraftPublisher;
use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;

class StandaloneThemeDraftPublisher implements ThemeDraftPublisher
{
    public function publish(ThemeStudioSettings $settings): ThemeStudioSettings
    {
        $settings->activeTheme = $settings->draftTheme;
        $settings->activePreset = $settings->draftPreset;
        $settings->draftTheme = null;
        $settings->draftPreset = null;
        $settings->draftWorkspaceId = null;
        $settings->save();

        return $settings;
    }

    public function requiresApproval(): bool
    {
        return false;
    }
}
