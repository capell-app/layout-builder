<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Admin\Services\Creator\ThemeCreator;
use Capell\Core\Enums\DefaultColorEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
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

        $type = $themeCreator->createDefaultThemeType();

        $theme = DB::transaction(function () use ($type): Theme {
            Theme::default()->update(['default' => false]);

            return Theme::updateOrCreate(
                ['key' => LayoutServiceProvider::$name],
                [
                    'name' => CapellCore::getPackage('capell-layout')->shortName,
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
                            'resources/js/capell-layout.js',
                        ],
                        'rounded_images' => true,
                        'header' => true,
                        'header_fixed' => true,
                        'footer' => true,
                        'colors' => DefaultColorEnum::getKeyValues(),
                        'link_color' => 'rgb(91, 204, 228)',
                    ],
                ]
            );
        });

        if (Site::count() === 1) {
            $site = Site::first();
            $site->theme_id = $theme->id;
            $site->save();
        }

        return $theme->refresh();
    }
}
