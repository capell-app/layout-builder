<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Actions;

use Capell\ThemeStudio\Admin\Contracts\ThemeDraftPublisher;
use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Lorisleiva\Actions\Concerns\AsObject;

class PublishThemeDraftAction
{
    use AsObject;

    public function handle(): ThemeStudioSettings
    {
        $settings = resolve(ThemeStudioSettings::class);

        if ($settings->draftTheme === null || $settings->draftPreset === null) {
            return $settings;
        }

        resolve(ThemeRegistry::class)
            ->definition($settings->draftTheme)
            ->presetOrFail($settings->draftPreset);

        return resolve(ThemeDraftPublisher::class)->publish($settings);
    }
}
