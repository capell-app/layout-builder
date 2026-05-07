<?php

declare(strict_types=1);

use Capell\Blog\Actions\InstallPackageAction;
use Illuminate\Console\Command;

use function Pest\Laravel\artisan;

it('runs blog setup through the package install action', function (): void {
    InstallPackageAction::shouldRun()->once();

    artisan('capell:blog-setup')
        ->expectsOutput('Capell Blog setup successfully.')
        ->assertExitCode(Command::SUCCESS);
});
