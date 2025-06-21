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
                    'header' => true,
                    'header_fixed' => true,
                    'footer' => true,
                    'colors' => [
                        'base' => 'rgb(38, 37, 37)',
                        'primary' => 'rgb(47, 199, 232)',
                        'secondary' => 'rgb(136, 186, 191)',
                        'light-gray' => 'rgb(242, 242, 242)',
                        'gray' => 'rgb(162, 156, 149)',
                    ],
                    'link_color' => 'rgb(174, 230, 242)',
                ],
                'admin' => [

                ],
            ]
        );
    }
}
