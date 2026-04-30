<?php

declare(strict_types=1);

namespace Capell\Mosaic\Tests\Integration\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

it('reports the created view path and prints the seeder snippet', function (): void {
    File::delete(resource_path('views/widgets/hero-banner.blade.php'));

    artisan('capell:mosaic-make-widget', ['name' => 'HeroBanner'])
        ->expectsOutputToContain(resource_path('views/widgets/hero-banner.blade.php'))
        ->expectsOutputToContain("'component' => 'widgets.hero-banner'")
        ->assertExitCode(Command::SUCCESS);
});

it('supports livewire widget scaffolding', function (): void {
    File::delete(resource_path('views/widgets/card-promo.blade.php'));
    File::delete(resource_path('views/widgets/livewire/card-promo.blade.php'));
    File::delete(app_path('Livewire/Widgets/CardPromoWidget.php'));

    artisan('capell:mosaic-make-widget', ['name' => 'CardPromo', '--livewire' => true])
        ->expectsOutputToContain(app_path('Livewire/Widgets/CardPromoWidget.php'))
        ->expectsOutputToContain(resource_path('views/widgets/livewire/card-promo.blade.php'))
        ->assertExitCode(Command::SUCCESS);
});
