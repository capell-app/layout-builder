<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Media\Pages\ListMedia;
use Capell\Admin\Filament\Resources\Sites\SiteResource;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('layout');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can list media', function (): void {
    $site = Site::factory()->create();
    $media = Media::factory()
        ->model($site)
        ->create();

    $records = collect([$media]);

    foreach (MediaCollectionEnum::cases() as $collection) {
        $mediaType = Media::factory()
            ->model(Page::factory()->site($site)->create())
            ->collection($collection)
            ->create();

        $records->push($mediaType);
    }

    livewire(ListMedia::class)
        ->assertSuccessful()
        ->assertCountTableRecords($records->count())
        ->assertCanSeeTableRecords($records)
        ->assertActionHasUrl(
            TestAction::make('edit')->table($media),
            SiteResource::getUrl('edit', ['record' => $site->id]),
        );
});
