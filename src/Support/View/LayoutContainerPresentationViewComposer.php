<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\View;

use Capell\Core\Models\Theme;
use Capell\Frontend\Support\CapellFrontendContext;
use Capell\LayoutBuilder\Actions\ResolveLayoutContainerPresentationAction;
use Illuminate\Contracts\View\View;

final readonly class LayoutContainerPresentationViewComposer
{
    public function compose(View $view): void
    {
        $container = $view->getData()['container'] ?? null;
        $containerKey = $view->getData()['containerKey'] ?? null;

        if (! is_array($container)) {
            return;
        }

        $view->with('presentation', ResolveLayoutContainerPresentationAction::run(
            container: $container,
            themeKey: $this->activeThemeKey(),
            containerKey: is_string($containerKey) ? $containerKey : null,
        ));
    }

    private function activeThemeKey(): ?string
    {
        if (! app()->bound(CapellFrontendContext::class)) {
            return null;
        }

        $context = resolve(CapellFrontendContext::class);
        if (! $context instanceof CapellFrontendContext) {
            return null;
        }

        $theme = $context->theme();
        $themeKey = $theme instanceof Theme ? $theme->getAttribute('key') : null;

        return is_string($themeKey) && trim($themeKey) !== '' ? trim($themeKey) : null;
    }
}
