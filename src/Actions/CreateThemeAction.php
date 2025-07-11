<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Admin\Services\Creator\ThemeCreator;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Theme;
use Capell\Layout\LayoutServiceProvider;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Theme run()
 */
class CreateThemeAction
{
    use AsObject;

    public function handle(): Theme
    {
        $themeCreator = app(ThemeCreator::class);

        $type = $themeCreator->createThemeTypes();

        return DB::transaction(function () use ($type): Theme {
            Theme::default()->update(['default' => false]);

            return Theme::updateOrCreate(
                ['key' => LayoutServiceProvider::$name],
                [
                    'name' => CapellCore::getPackage('capell-layout')->name,
                    'type_id' => $type->id,
                    'status' => true,
                    'default' => true,
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
        });
    }
}
