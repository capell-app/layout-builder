<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Actions;

use Capell\ThemeStudio\Admin\Contracts\ThemeDraftPublisher;
use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;
use Lorisleiva\Actions\Concerns\AsObject;

class ResolveThemePublishingReadinessAction
{
    use AsObject;

    /**
     * @return array{complete: bool, description: string}
     */
    public function handle(ThemeStudioSettings $settings): array
    {
        $publisher = resolve(ThemeDraftPublisher::class);

        if ($publisher->requiresApproval()) {
            return [
                'complete' => true,
                'description' => __('capell-theme-studio-admin::studio.readiness.preview_description_workspace'),
            ];
        }

        return [
            'complete' => $settings->draftTheme !== null,
            'description' => __('capell-theme-studio-admin::studio.readiness.preview_description_standalone'),
        ];
    }
}
