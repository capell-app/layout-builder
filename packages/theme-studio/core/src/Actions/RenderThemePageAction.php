<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Actions;

use Capell\ThemeStudio\Core\Data\BrandProfileData;
use Capell\ThemeStudio\Core\Data\ThemePageData;
use Capell\ThemeStudio\Core\Preview\ThemePreviewContext;
use Lorisleiva\Actions\Concerns\AsObject;

class RenderThemePageAction
{
    use AsObject;

    /**
     * @param  array<string, array<string, mixed>>  $themeOverrides
     */
    public function handle(
        ThemePageData $page,
        string $activeTheme,
        string $activePreset,
        ?BrandProfileData $brand = null,
        array $themeOverrides = [],
        ?ThemePreviewContext $previewContext = null,
    ): string {
        $runtime = ResolveThemeRuntimeAction::run(
            activeTheme: $activeTheme,
            activePreset: $activePreset,
            brand: $brand ?? $page->brand,
            themeOverrides: $themeOverrides,
            previewContext: $previewContext,
        );

        return $runtime->renderer->render(new ThemePageData(
            title: $page->title,
            brand: $runtime->brand,
            sections: $page->sections,
            navigation: $page->navigation,
            footer: $page->footer,
        ));
    }
}
