<?php

declare(strict_types=1);

namespace Capell\Layout\Support\Interceptors\Themes;

use Capell\Core\Contracts\ModelInterceptors\ThemeInterceptorInterface;
use Capell\Core\Models\Theme;

final class DefaultThemeInterceptor implements ThemeInterceptorInterface
{
    public function beforeCreate(array $data): array
    {
        if (! isset($data['vendor_assets'])) {
            $data['vendor_assets'] = [];
        }

        $data['vendor_assets'][] = [
            'path' => 'vendor/capell-layout/frontend',
            'file' => 'resources/js/capell-layout.js',
        ];

        return $data;
    }

    public function afterCreated(Theme $theme, array $data): void {}
}
