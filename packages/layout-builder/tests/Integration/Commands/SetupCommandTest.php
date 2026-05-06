<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Integration\Commands;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Actions\AddHeroWidgetToLayoutAction;
use Capell\LayoutBuilder\Actions\InstallPackageAction;
use Capell\LayoutBuilder\Providers\LayoutBuilderServiceProvider;
use Illuminate\Console\Command;

use function Pest\Laravel\artisan;

it('registers install setup and demo commands for capell install flows', function (): void {
    $package = CapellCore::getPackage(LayoutBuilderServiceProvider::$packageName);

    expect($package->getInstallCommand())->toBe('capell:layout-builder-install')
        ->and($package->getSetupCommand())->toBe('capell:layout-builder-setup')
        ->and($package->getDemoCommand())->toBe('capell:layout-builder-demo')
        ->and($package->getDemoParams())->toBe(['sites', 'user']);
});

it('runs layout-builder setup command successfully', function (): void {
    InstallPackageAction::shouldRun()->once();

    artisan('capell:layout-builder-setup')
        ->expectsOutput('Capell LayoutBuilder setup successfully.')
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
