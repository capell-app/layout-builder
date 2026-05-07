<?php

declare(strict_types=1);

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Redirects\Policies\RedirectPolicy;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection as SupportCollection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

function redirectPolicyPermission(string $affix): string
{
    $permissions = Utils::getConfig()->permissions;

    return FilamentShield::defaultPermissionKeyBuilder(
        affix: $affix,
        separator: $permissions->separator,
        subject: 'PageUrl',
        case: $permissions->case,
    );
}

function createScopedUserForRedirectPolicyTest(SupportCollection $assignedSiteIds): Authenticatable
{
    $user = new class extends Authenticatable implements FilamentUser
    {
        use HasFactory;
        use HasRoles;

        /** @var SupportCollection<int, int> */
        public SupportCollection $assignedSiteIds;

        protected $table = 'users';

        protected string $guard_name = 'web';

        public function canAccessPanel(Panel $panel): bool
        {
            return true;
        }

        public function isGlobalAdmin(): bool
        {
            return false;
        }

        /** @return SupportCollection<int, int> */
        public function getAssignedSiteIds(): SupportCollection
        {
            return $this->assignedSiteIds;
        }
    };

    Relation::morphMap(['redirect-policy-user' => $user::class], merge: true);

    $user->forceFill([
        'name' => 'Scoped Redirect Policy User',
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
    ]);
    $user->assignedSiteIds = $assignedSiteIds;
    $user->save();

    return $user;
}

beforeEach(function (): void {
    foreach ([
        'view_any',
        'view',
        'create',
        'update',
        'delete',
        'delete_any',
        'restore',
        'restore_any',
        'force_delete',
        'force_delete_any',
        'import',
        'export',
    ] as $permissionAffix) {
        Permission::findOrCreate(redirectPolicyPermission($permissionAffix), 'web');
    }
});

it('allows list access with either view any or view permission', function (): void {
    $viewAnyUser = createScopedUserForRedirectPolicyTest(collect());
    $viewAnyUser->givePermissionTo(redirectPolicyPermission('view_any'));

    $viewUser = createScopedUserForRedirectPolicyTest(collect());
    $viewUser->givePermissionTo(redirectPolicyPermission('view'));

    $unpermittedUser = createScopedUserForRedirectPolicyTest(collect());

    $policy = new RedirectPolicy;

    expect($policy->viewAny($viewAnyUser))->toBeTrue()
        ->and($policy->viewAny($viewUser))->toBeTrue()
        ->and($policy->viewAny($unpermittedUser))->toBeFalse();
});

it('allows users through role-granted redirect permissions', function (): void {
    $redirectManagerRole = Role::findOrCreate('redirect-manager', 'web');
    $redirectManagerRole->givePermissionTo(redirectPolicyPermission('view_any'));

    $user = createScopedUserForRedirectPolicyTest(collect());
    $user->assignRole($redirectManagerRole);

    expect((new RedirectPolicy)->viewAny($user))->toBeTrue();
});

it('requires record permissions and assigned site access for redirect records', function (): void {
    $assignedSite = Site::factory()->create();
    $otherSite = Site::factory()->create();
    $assignedRedirect = PageUrl::factory()->site($assignedSite)->create();
    $otherRedirect = PageUrl::factory()->site($otherSite)->create();

    $scopedUser = createScopedUserForRedirectPolicyTest(collect([$assignedSite->getKey()]));
    $scopedUser->givePermissionTo([
        redirectPolicyPermission('view'),
        redirectPolicyPermission('update'),
        redirectPolicyPermission('delete'),
        redirectPolicyPermission('restore'),
        redirectPolicyPermission('force_delete'),
    ]);

    $policy = new RedirectPolicy;

    expect($policy->view($scopedUser, $assignedRedirect))->toBeTrue()
        ->and($policy->update($scopedUser, $assignedRedirect))->toBeTrue()
        ->and($policy->delete($scopedUser, $assignedRedirect))->toBeTrue()
        ->and($policy->restore($scopedUser, $assignedRedirect))->toBeTrue()
        ->and($policy->forceDelete($scopedUser, $assignedRedirect))->toBeTrue()
        ->and($policy->view($scopedUser, $otherRedirect))->toBeFalse()
        ->and($policy->update($scopedUser, $otherRedirect))->toBeFalse()
        ->and($policy->delete($scopedUser, $otherRedirect))->toBeFalse()
        ->and($policy->restore($scopedUser, $otherRedirect))->toBeFalse()
        ->and($policy->forceDelete($scopedUser, $otherRedirect))->toBeFalse();
});

it('requires matching permissions for collection and custom abilities', function (): void {
    $permittedUser = createScopedUserForRedirectPolicyTest(collect());
    $permittedUser->givePermissionTo([
        redirectPolicyPermission('create'),
        redirectPolicyPermission('delete_any'),
        redirectPolicyPermission('restore_any'),
        redirectPolicyPermission('force_delete_any'),
        redirectPolicyPermission('import'),
        redirectPolicyPermission('export'),
    ]);
    $unpermittedUser = createScopedUserForRedirectPolicyTest(collect());

    $policy = new RedirectPolicy;

    expect($policy->create($permittedUser))->toBeTrue()
        ->and($policy->deleteAny($permittedUser))->toBeTrue()
        ->and($policy->restoreAny($permittedUser))->toBeTrue()
        ->and($policy->forceDeleteAny($permittedUser))->toBeTrue()
        ->and($policy->import($permittedUser))->toBeTrue()
        ->and($policy->export($permittedUser))->toBeTrue()
        ->and($policy->create($unpermittedUser))->toBeFalse()
        ->and($policy->deleteAny($unpermittedUser))->toBeFalse()
        ->and($policy->restoreAny($unpermittedUser))->toBeFalse()
        ->and($policy->forceDeleteAny($unpermittedUser))->toBeFalse()
        ->and($policy->import($unpermittedUser))->toBeFalse()
        ->and($policy->export($unpermittedUser))->toBeFalse();
});
