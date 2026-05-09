<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Diagnostics\Filament\Widgets\Health\CacheHealthWidgetAbstract as CacheHealthWidget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Gate;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Role;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    Role::findOrCreate('super_admin');
    Role::findOrCreate(config('capell.roles.developer', 'developer'));
});

function createScopedUserForCacheHealthWidgetTest(SupportCollection $assignedSiteIds): Authenticatable
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
        'name' => 'Scoped Cache User',
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
    ]);
    $user->assignedSiteIds = $assignedSiteIds;

    return $user;
}

it('renders for an admin', function (): void {
    Site::factory()->withTranslations()->create();

    $user = $this->createUser();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    livewire(CacheHealthWidget::class)->assertOk();
});

it('limits cache health sites to assigned sites for non-global users', function (): void {
    Gate::before(fn (): bool => true);

    $assignedSite = Site::factory()->withTranslations()->create(['name' => 'Assigned Site']);
    Site::factory()->withTranslations()->create(['name' => 'Other Site']);

    test()->actingAs(createScopedUserForCacheHealthWidgetTest(collect([$assignedSite->getKey()])));

    livewire(CacheHealthWidget::class)
        ->assertOk()
        ->assertSet('selectedSiteId', $assignedSite->getKey())
        ->assertDontSee('Other Site');
});

it('does not warm cache for unassigned sites', function (): void {
    Gate::before(fn (): bool => true);

    $assignedSite = Site::factory()->withTranslations()->create();
    $otherSite = Site::factory()->withTranslations()->create();

    test()->actingAs(createScopedUserForCacheHealthWidgetTest(collect([$assignedSite->getKey()])));

    livewire(CacheHealthWidget::class)
        ->set('selectedSiteId', $otherSite->getKey())
        ->call('warmCache')
        ->assertNotDispatched('$refresh');
});

it('refreshes when warmCache is invoked', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $user = $this->createUser();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    livewire(CacheHealthWidget::class)
        ->set('selectedSiteId', $site->id)
        ->call('warmCache')
        ->assertDispatched('$refresh');
});
