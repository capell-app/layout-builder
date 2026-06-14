<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\RenderHooks;

use Capell\Frontend\Contracts\RenderHookExtensionInterface;
use Capell\Frontend\Data\MainContentRenderHookData;
use Capell\Frontend\Data\RenderHookContext;
use Livewire\Blaze\Blaze;

final class RegisterMainContentLayoutHook implements RenderHookExtensionInterface
{
    public const string Scenario = 'frontend-main-layout';

    public const string Target = 'capell::layout.main';

    public function render(RenderHookContext $context): string
    {
        if (! $context->item instanceof MainContentRenderHookData) {
            return '';
        }

        $view = view('capell-layout-builder::components.layout.main-content', [
            'context' => $context->item,
        ]);

        $wasBlazeEnabled = Blaze::isEnabled();
        Blaze::disable();

        try {
            $output = $view->render();
        } finally {
            if ($wasBlazeEnabled) {
                Blaze::enable();
            }
        }

        return is_string($output) && trim($output) !== '' ? $output : '';
    }
}
