<?php

declare(strict_types=1);

use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Tests\Support\Filament\PageReplicationAssertions;

uses(CreatesAdminUser::class);

it('mounts and submits the page replicate modal with translations and its existing layout', function (string $entryPoint): void {
    $this->actingAsAdmin();
    PageReplicationAssertions::assert($entryPoint);
})->with(['header', 'row']);
