<?php

declare(strict_types=1);

namespace Capell\DefaultTheme\Support\Interceptors\Themes;

use Capell\Core\Contracts\ModelInterceptors\ThemeInterceptorInterface;
use Capell\Core\Enums\DefaultColorEnum;
use Capell\Core\Models\Theme;

final class DefaultThemeInterceptor implements ThemeInterceptorInterface
{
    public function beforeCreate(array $data): array
    {
        if (! isset($data['meta'])) {
            $data['meta'] = [];
        }

        $data['meta'] = array_merge([
            'header_border_color' => DefaultColorEnum::LightGray->getColor(),
            'sticky_header' => true,
            'dark_mode_toggle' => true,
            'content_divider' => 'below_heading',
        ], $data['meta']);

        return $data;
    }

    public function afterCreated(Theme $theme, array $data): void {}
}
