<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Actions;

use Capell\ThemeStudio\Core\Contracts\ThemePageAdapter;
use Capell\ThemeStudio\Core\Contracts\ThemeRuntimeSettings;
use Lorisleiva\Actions\Concerns\AsObject;

class RenderCurrentThemePageAction
{
    use AsObject;

    public function handle(): string
    {
        $settings = resolve(ThemeRuntimeSettings::class);
        $page = resolve(ThemePageAdapter::class)->currentPage();

        return RenderThemePageAction::run(
            page: $page,
            activeTheme: $settings->activeTheme(),
            activePreset: $settings->activePreset(),
            brand: $settings->brandProfile(),
            themeOverrides: $settings->themeOverrides(),
        );
    }
}
