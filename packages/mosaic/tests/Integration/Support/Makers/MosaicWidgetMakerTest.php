<?php

declare(strict_types=1);

namespace Capell\Mosaic\Tests\Integration\Support\Makers;

use Capell\Core\Actions\Makers\RunMakerAction;
use Capell\Core\Data\Makers\MakerInputData;
use Capell\Mosaic\Support\Makers\MosaicWidgetMaker;
use Illuminate\Support\Facades\File;

afterEach(function (): void {
    File::delete(resource_path('views/widgets/hero-banner.blade.php'));
});

it('previews blade and livewire widget files', function (): void {
    $preview = resolve(MosaicWidgetMaker::class)->preview(new MakerInputData(
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

it('overwrites existing widget files when the registry maker is forced', function (): void {
    $path = resource_path('views/widgets/hero-banner.blade.php');

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    file_put_contents($path, 'custom content');

    $result = RunMakerAction::run(new MakerInputData(
        maker: 'mosaic.widget',
        values: ['name' => 'Hero Banner'],
        dryRun: false,
        force: true,
        databaseWrites: false,
    ));

    expect($result->files->first()->operation)->toBe('overwrite');
    expect(file_get_contents($path))->toContain('HeroBanner widget');
});
