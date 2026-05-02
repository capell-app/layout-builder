<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection as SupportCollection;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('navigation');

function createScopedUserForNavigationResourceTest(SupportCollection $assignedSiteIds): Authenticatable
{
    $user = new class extends Authenticatable implements FilamentUser
    {
        use HasFactory;

        /** @var SupportCollection<int, int> */
        public SupportCollection $assignedSiteIds;

        protected $table = 'users';

        public function canAccessPanel(Panel $panel): bool
        {
            return true;
        }

        /** @return SupportCollection<int, int> */
        public function getAssignedSiteIds(): SupportCollection
        {
            return $this->assignedSiteIds;
        }

        public function isGlobalAdmin(): bool
        {
            return false;
        }
    };

    $user->forceFill([
        'name' => 'Scoped Navigation User',
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
    ]);
    $user->assignedSiteIds = $assignedSiteIds;

    return $user;
}

test('admin can see navigations', function (): void {
    test()->actingAsAdmin();

    get(NavigationResource::getUrl())
        ->assertOk();
});

test('cannot see navigations', function (): void {
    test()->actingAsUser();

    get(NavigationResource::getUrl())
        ->assertForbidden();
});

test('admin can see create navigation', function (): void {
    test()->actingAsAdmin();

    get(NavigationResource::getUrl('create'))->assertOk();
});

test('admin can see edit navigation', function (): void {
    test()->actingAsAdmin();

    $language = Language::factory()->create();
    get(NavigationResource::getUrl('edit', ['record' => Navigation::factory()->language($language)->create()]))->assertOk();
});

test('navigation queries include globals and assigned sites only for scoped users', function (): void {
    $assignedSite = Site::factory()->create();
    $otherSite = Site::factory()->create();
    $globalNavigation = Navigation::factory()->create(['site_id' => null]);
    $assignedNavigation = Navigation::factory()->site($assignedSite)->create();
    Navigation::factory()->site($otherSite)->create();

    test()->actingAs(createScopedUserForNavigationResourceTest(collect([$assignedSite->getKey()])));

    expect(NavigationResource::getEloquentQuery()->pluck('id')->all())
        ->toEqualCanonicalizing([$globalNavigation->getKey(), $assignedNavigation->getKey()])
        ->and(NavigationResource::getGlobalSearchEloquentQuery()->pluck('id')->all())
        ->toEqualCanonicalizing([$globalNavigation->getKey(), $assignedNavigation->getKey()]);
});
