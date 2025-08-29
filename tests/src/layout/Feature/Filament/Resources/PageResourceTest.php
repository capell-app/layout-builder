<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Core\Models\Page;
use Capell\Layout\Livewire\LayoutBuilder;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('page');

test('can see layout builder', function (): void {
    test()->actingAsAdmin();

    $page = Page::factory()->create();

    get(PageResource::getUrl('edit', ['record' => $page]))
        ->assertSeeLivewire(LayoutBuilder::class);
});
