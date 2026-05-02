<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Tags\Filament\Resources\Tags\TagResource;
use Capell\Tags\Models\Tag;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection as SupportCollection;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('tag');

function createScopedUserForTagResourceTest(SupportCollection $assignedSiteIds): Authenticatable
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
        'name' => 'Scoped Tag User',
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
    ]);
    $user->assignedSiteIds = $assignedSiteIds;

    return $user;
}

test('admin can see tags', function (): void {
    test()->actingAsAdmin();

    Language::factory()->create();

    get(TagResource::getUrl())
        ->assertOk();
});

test('cannot see tags', function (): void {
    test()->actingAsUser();

    get(TagResource::getUrl())
        ->assertForbidden();
});

test('tag queries include globals and assigned sites only for scoped users', function (): void {
    $assignedSite = Site::factory()->create();
    $otherSite = Site::factory()->create();
    $globalTag = Tag::factory()->create(['site_id' => null]);
    $assignedTag = Tag::factory()->site($assignedSite)->create();
    Tag::factory()->site($otherSite)->create();

    test()->actingAs(createScopedUserForTagResourceTest(collect([$assignedSite->getKey()])));

    expect(TagResource::getEloquentQuery()->pluck('id')->all())
        ->toEqualCanonicalizing([$globalTag->getKey(), $assignedTag->getKey()])
        ->and(TagResource::getGlobalSearchEloquentQuery()->pluck('id')->all())
        ->toEqualCanonicalizing([$globalTag->getKey(), $assignedTag->getKey()]);
});
