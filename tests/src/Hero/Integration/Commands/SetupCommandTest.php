<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Hero\Actions\AddHeroToLayoutAction;
use Illuminate\Console\Command;

it('runs hero install command successfully', function (): void {
    $layout = Layout::factory()->create();

    $mock = AddHeroToLayoutAction::mock();
    $mock->shouldReceive('handle')->once();
    app()->instance(AddHeroToLayoutAction::class, $mock);

    $this->artisan('capell:hero-setup')
        ->expectsOutput('Capell Hero setup successfully.')
        ->assertExitCode(Command::SUCCESS);
});
