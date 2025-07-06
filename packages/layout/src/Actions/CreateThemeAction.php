<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Core\Models\Theme;
use Capell\Core\Models\Type;
use Capell\Layout\LayoutServiceProvider;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Theme run()
 */
class CreateThemeAction
{
    use AsObject;

    public function handle(): Theme
    {
        return Theme::updateOrCreate(
            ['key' => LayoutServiceProvider::$name],
            [
                'name' => 'Capell Layout',
                'type_id' => Type::themeType()->value('id'),
                'status' => true,
                'meta' => [
                    'build_path' => 'vendor/capell-layout',
                    'vendor_assets' => [
                        'resources/js/capell-frontend.js',
                    ],
                    'assets' => [
                        'resources/css/capell-layout.css',
                    ],
                    'rounded_images' => true,
                    'header' => true,
                    'header_fixed' => true,
                    'footer' => true,
                    'colors' => config('capell-admin.colors'),
                    'link_color' => 'rgb(91, 204, 228)',
                ],
            ]
        );
    }
}
