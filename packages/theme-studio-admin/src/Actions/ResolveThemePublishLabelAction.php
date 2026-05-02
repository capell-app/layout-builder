<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Actions;

use Capell\ThemeStudio\Admin\Contracts\ThemeDraftPublisher;
use Lorisleiva\Actions\Concerns\AsObject;

class ResolveThemePublishLabelAction
{
    use AsObject;

    public function handle(): string
    {
        $publisher = resolve(ThemeDraftPublisher::class);

        if ($publisher->requiresApproval()) {
            return __('capell-theme-studio-admin::studio.submit_for_approval');
        }

        return __('capell-theme-studio-admin::studio.publish');
    }
}
