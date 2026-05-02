<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Filament\Pages\ActivityTrailPage;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class);

describe('ActivityTrailPage', function (): void {
    it('can access the activity trail page', function (): void {
        test()->actingAsAdmin();

        get(ActivityTrailPage::getUrl())
            ->assertSuccessful();
    });

    it('displays activity records in table', function (): void {
        test()->actingAsAdmin();

        $page = Page::factory()->create();
        activity()->causedBy(auth()->user())->performedOn($page)->log('created');

        get(ActivityTrailPage::getUrl())
            ->assertSuccessful()
            ->assertSeeText('Activity Trail');
    });

    it('denies access to unauthorized users', function (): void {
        test()->actingAsUser();

        get(ActivityTrailPage::getUrl())
            ->assertForbidden();
    });
});
