<?php

declare(strict_types=1);

use Capell\Tests\Fixtures\Models\User;

use function Pest\Laravel\artisan;

it('runs the install command successfully', function (): void {
    $user = User::factory()->create();

    artisan('capell:install')
        ->expectsQuestion('What is the URL of the site?', 'http://localhost')
        ->expectsQuestion('Install the following packages?', [
            'admin',
            'layout',
        ])
        ->expectsQuestion('Do you want to add demo content?', false)
        ->expectsOutput('Admin package installed successfully.')
        ->assertSuccessful();
})
    ->skip('Skipped due to migrations missing not deleting migrations from testbench-core');
