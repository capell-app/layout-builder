<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Actions\DashboardReports\BuildSeoAuditQueryAction;
use Capell\SeoSuite\Actions\DashboardReports\BuildTranslationCoverageQueryAction;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection as SupportCollection;

function createScopedUserForAdminReportSiteScopeTest(SupportCollection $assignedSiteIds): Authenticatable
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
        'name' => 'Scoped Report User',
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
    ]);
    $user->assignedSiteIds = $assignedSiteIds;

    return $user;
}

it('limits SEO audit dashboard-dashboard_reports to assigned sites for non-global users', function (): void {
    $assignedSite = Site::factory()->withTranslations()->create();
    $otherSite = Site::factory()->withTranslations()->create();
    $assignedPage = Page::factory()
        ->recycle($assignedSite)
        ->withTranslations(data: ['meta' => []])
        ->create();
    Page::factory()
        ->recycle($otherSite)
        ->withTranslations(data: ['meta' => []])
        ->create();

    test()->actingAs(createScopedUserForAdminReportSiteScopeTest(collect([$assignedSite->getKey()])));

    expect(BuildSeoAuditQueryAction::run()->pluck('id')->all())->toBe([$assignedPage->getKey()]);
});

it('limits translation coverage dashboard-dashboard_reports to assigned sites for non-global users', function (): void {
    $assignedSite = Site::factory()->withTranslations()->create();
    $otherSite = Site::factory()->withTranslations()->create();
    $assignedPage = Page::factory()->recycle($assignedSite)->create();
    Page::factory()->recycle($otherSite)->create();

    test()->actingAs(createScopedUserForAdminReportSiteScopeTest(collect([$assignedSite->getKey()])));

    expect(BuildTranslationCoverageQueryAction::run()->pluck('id')->all())->toBe([$assignedPage->getKey()]);
});

it('denies report rows for non-global users without assigned sites', function (): void {
    Page::factory()
        ->count(2)
        ->withTranslations(data: ['meta' => []])
        ->create();

    test()->actingAs(createScopedUserForAdminReportSiteScopeTest(collect()));

    expect(BuildSeoAuditQueryAction::run()->count())->toBe(0)
        ->and(BuildTranslationCoverageQueryAction::run()->count())->toBe(0);
});
