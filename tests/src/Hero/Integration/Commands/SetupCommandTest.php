<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Hero\Actions\AddHeroToLayoutAction;
use Illuminate\Console\Command;

use function Pest\Laravel\artisan;

it('runs hero install command successfully', function (): void {
    AddHeroToLayoutAction::shouldRun()->once();

    Layout::factory()->create();

    artisan('capell:hero-setup')
        ->expectsOutput('Capell Hero setup successfully.')
        ->assertExitCode(Command::SUCCESS);
});
