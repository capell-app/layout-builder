<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\RenderHooks;

use Capell\Frontend\Data\MainContentRenderHookData;
use Capell\Frontend\Data\RenderHookContext;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Livewire\Blaze\Blaze;

final class RegisterMainContentLayoutHook
{
    private const string Scenario = 'frontend-main-layout';

    private const string Target = 'capell::layout.main';

    /** @param RenderHookRegistry<RenderHookContext> $registry */
    public function __construct(private readonly RenderHookRegistry $registry) {}

    public function register(): void
    {
        $this->registry->register(
            RenderHookLocation::MainContent,
            static function (RenderHookContext $context): string {
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
            },
            scenario: self::Scenario,
            target: self::Target,
        );
    }
}
