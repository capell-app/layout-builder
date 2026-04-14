<?php

declare(strict_types=1);

use function Pest\Laravel\artisan;

it('runs clear AI cache command successfully', function (): void {
    artisan('capell:admin-clear-ai-cache')
        ->assertExitCode(0);
});
