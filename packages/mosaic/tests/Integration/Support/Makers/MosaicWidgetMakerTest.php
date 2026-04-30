<?php

declare(strict_types=1);

namespace Capell\Mosaic\Tests\Integration\Support\Makers;

use Capell\Core\Data\Makers\MakerInputData;
use Capell\Mosaic\Support\Makers\MosaicWidgetMaker;

it('previews blade and livewire widget files', function (): void {
    $preview = app(MosaicWidgetMaker::class)->preview(new MakerInputData(
        maker: 'mosaic.widget',
        values: ['name' => 'Hero Banner', 'livewire' => true],
        dryRun: true,
        force: false,
        databaseWrites: false,
    ));

    expect($preview->files->pluck('path')->all())
        ->toContain(resource_path('views/widgets/hero-banner.blade.php'))
        ->toContain(app_path('Livewire/Widgets/HeroBannerWidget.php'))
        ->toContain(resource_path('views/widgets/livewire/hero-banner.blade.php'));

    expect($preview->notes->first())->toContain("'component' => 'widgets.hero-banner'");
});
