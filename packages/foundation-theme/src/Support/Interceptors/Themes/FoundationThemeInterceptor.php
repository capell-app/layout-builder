<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Interceptors\Themes;

use Capell\Core\Contracts\ModelInterceptors\ThemeInterceptorInterface;
use Capell\Core\Enums\DefaultColorEnum;
use Capell\Core\Models\Theme;

final class FoundationThemeInterceptor implements ThemeInterceptorInterface
{
    public function beforeCreate(array $data): array
    {
        if (! isset($data['meta'])) {
            $data['meta'] = [];
        }

        $data['meta'] = array_merge([
            'assets' => ['resources/css/capell/frontend.css'],
            'assets_path' => 'build',
            'header_divider' => false,
            'header_border_color' => DefaultColorEnum::LightGray->getColor(),
            'header_shadow' => 'none',
            'footer_background_color' => '#f1f5f9',
            'footer_divider' => false,
            'footer_border_color' => '#e2e8f0',
            'footer_color' => '#1f2937',
            'footer_dark_background_color' => '#111827',
            'footer_dark_border_color' => '#374151',
            'footer_dark_color' => '#e5e7eb',
            'footer_spacing' => 'compact',
            'sticky_header' => true,
            'dark_mode_toggle' => true,
            'content_divider' => 'below_heading',
        ], $data['meta']);

        return $data;
    }

    public function afterCreated(Theme $theme, array $data): void {}
}
