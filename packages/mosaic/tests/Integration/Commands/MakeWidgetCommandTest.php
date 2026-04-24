<?php

declare(strict_types=1);

namespace Capell\Mosaic\Tests\Integration\Commands;

use Capell\Mosaic\Actions\MakeWidgetAction;
use Capell\Mosaic\Data\WidgetScaffoldData;
use Illuminate\Console\Command;

use function Pest\Laravel\artisan;

it('reports the created view path and prints the seeder snippet', function (): void {
    MakeWidgetAction::shouldRun()
        ->once()
        ->andReturn(new WidgetScaffoldData(
            viewPath: '/tmp/views/widgets/hero-banner.blade.php',
            created: true,
            seederSnippet: '// seeder snippet',
        ));

    artisan('capell:mosaic-make-widget', ['name' => 'HeroBanner'])
        ->expectsOutputToContain('/tmp/views/widgets/hero-banner.blade.php')
        ->expectsOutputToContain('// seeder snippet')
        ->assertExitCode(Command::SUCCESS);
});

it('warns when the view already exists and still prints the snippet', function (): void {
    MakeWidgetAction::shouldRun()
        ->once()
        ->andReturn(new WidgetScaffoldData(
            viewPath: '/tmp/views/widgets/hero-banner.blade.php',
            created: false,
            seederSnippet: '// seeder snippet',
        ));

    artisan('capell:mosaic-make-widget', ['name' => 'HeroBanner'])
        ->expectsOutputToContain('already exists')
        ->expectsOutputToContain('// seeder snippet')
        ->assertExitCode(Command::SUCCESS);
});
