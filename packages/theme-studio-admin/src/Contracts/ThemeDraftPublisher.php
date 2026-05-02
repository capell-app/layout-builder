<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Contracts;

use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;

interface ThemeDraftPublisher
{
    public function publish(ThemeStudioSettings $settings): ThemeStudioSettings;

    public function requiresApproval(): bool;
}
