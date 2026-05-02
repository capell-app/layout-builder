<?php

declare(strict_types=1);

namespace Capell\Mosaic\Tests\Integration\Commands;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Mosaic\Actions\AddHeroWidgetToLayoutAction;
use Capell\Mosaic\Actions\InstallPackageAction;
use Capell\Mosaic\Providers\MosaicServiceProvider;
use Illuminate\Console\Command;

use function Pest\Laravel\artisan;

it('registers install setup and demo commands for capell install flows', function (): void {
    $package = CapellCore::getPackage(MosaicServiceProvider::$packageName);

    expect($package->getInstallCommand())->toBe('capell:mosaic-install')
        ->and($package->getSetupCommand())->toBe('capell:mosaic-setup')
        ->and($package->getDemoCommand())->toBe('capell:mosaic-demo')
        ->and($package->getDemoParams())->toBe(['sites', 'user']);
});

it('runs mosaic setup command successfully', function (): void {
    InstallPackageAction::shouldRun()->once();

    artisan('capell:mosaic-setup')
        ->expectsOutput('Capell Mosaic setup successfully.')
        ->expectsOutput('Running hero setup...')
        ->expectsOutput('Capell Hero setup successfully.')
        ->assertExitCode(Command::SUCCESS);
});

it('runs hero setup command directly', function (): void {
    AddHeroWidgetToLayoutAction::shouldRun()->once();

    Layout::factory()->default()->create();

    artisan('capell:hero-setup')
        ->expectsOutput('Capell Hero setup successfully.')
        ->assertExitCode(Command::SUCCESS);
});
