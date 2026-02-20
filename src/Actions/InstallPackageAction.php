<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Core\Actions\AddVendorAssetToThemeAction;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Creator\LayoutCreator;
use Capell\Layout\Support\Creator\TypeCreator;
use Capell\Layout\Support\Creator\WidgetCreator;
use Capell\Layout\Support\LayoutModelRegistrar;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run()
 */
class InstallPackageAction
{
    use AsFake;
    use AsObject;

    public function handle(): void
    {
        LayoutModelRegistrar::register();

        $typeCreator = resolve(TypeCreator::class);
        $typeCreator->createWidgetTypes();

        $typeCreator->createDefaultContentType();
        $typeCreator->createBuilderContentType();

        $widgetCreator = resolve(WidgetCreator::class);
        $widgetCreator->createWidgets(Language::all());

        $layoutCreator = resolve(LayoutCreator::class);
        $layoutCreator->setup();

        $this->updateThemes();
    }

    private function updateThemes(): void
    {
        $path = 'vendor/capell-layout/frontend';

        /** @var class-string<Theme> $model */
        $model = CapellCore::getModel(CoreModelEnum::Theme);

        $model::query()
            ->lazy()
            ->each(
                function (Theme $theme) use ($path): void {
                    $vendorAssets = $theme->meta['vendor_assets'] ?? [];
                    $removeAssets = [
                        [
                            'path' => 'vendor/capell-frontend',
                            'file' => 'resources/css/capell-frontend.css',
                        ],
                    ];

                    $filteredAssets = array_filter(
                        $vendorAssets,
                        static fn (array $asset): bool => collect($removeAssets)
                            ->doesntContain(
                                static fn (array $removeAsset): bool => $asset['path'] === $removeAsset['path']
                                    && $asset['file'] === $removeAsset['file'],
                            ),
                    );

                    $theme->setAttribute('meta', array_replace(
                        $theme->meta,
                        [
                            'vendor_assets' => array_values($filteredAssets),
                        ],
                    ));

                    AddVendorAssetToThemeAction::run(
                        $theme,
                        $path,
                        [
                            'resources/js/capell-layout.js',
                        ],
                    );
                },
            );
    }
}
