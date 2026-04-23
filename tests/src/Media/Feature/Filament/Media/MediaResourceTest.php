<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Media\MediaResource;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('media');

test('admin can see media page', function (): void {
    test()->actingAsAdmin();

    get(MediaResource::getUrl())
        ->assertOk();
});
