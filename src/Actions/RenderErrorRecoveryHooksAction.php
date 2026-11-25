<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Frontend\Data\MainContentRenderHookData;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\LayoutBuilder\Support\RenderHooks\RegisterMainContentLayoutHook;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * On an error page the after-content hooks (recovery suggestions) belong with
 * the page content, not after whatever the layout places below it, such as the
 * footer. The layout renders them once, straight after the page-content widget,
 * and records that so the main layout does not render them again.
 */
final class RenderErrorRecoveryHooksAction
{
    use AsFake;
    use AsObject;

    public const string RenderedKey = 'layout-builder.error-recovery-rendered';

    public static function rendered(): bool
    {
        return Frontend::getFrontendData(self::RenderedKey) === true;
    }

    public function handle(MainContentRenderHookData $context): string
    {
        if (! Frontend::isError() || self::rendered()) {
            return '';
        }

        Frontend::setFrontendData(self::RenderedKey, true);

        return app(RenderHookRegistry::class)->renderAll(
            RenderHookLocation::AfterContent,
            $context,
            scenario: RegisterMainContentLayoutHook::Scenario,
            target: RegisterMainContentLayoutHook::Target,
        );
    }
}
