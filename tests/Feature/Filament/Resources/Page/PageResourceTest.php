<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Core\Models\Page;
use Capell\Mosaic\Enums\LivewireComponentsEnum;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('page');

test('can see layout builder', function (): void {
    test()->actingAsAdmin();

    $page = Page::factory()->create();

    get(PageResource::getUrl('edit', ['record' => $page]))
        ->assertSeeLivewire(LivewireComponentsEnum::LayoutBuilder->value);
});
