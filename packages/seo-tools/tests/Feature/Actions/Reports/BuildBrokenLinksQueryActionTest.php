<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoTools\Actions\Reports\BuildBrokenLinksQueryAction;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

function createScopedUserForBuildBrokenLinksQueryActionTest(SupportCollection $assignedSiteIds): Authenticatable
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
        'name' => 'Scoped Broken Links User',
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
    ]);
    $user->assignedSiteIds = $assignedSiteIds;

    return $user;
}

describe('BuildBrokenLinksQueryAction', function (): void {
    it('returns a query builder instance', function (): void {
        $query = BuildBrokenLinksQueryAction::run();

        expect($query)->toBeInstanceOf(Builder::class);
    });

    it('returns an empty result set when no broken links exist', function (): void {
        $result = BuildBrokenLinksQueryAction::run()->get();

        expect($result)->toBeEmpty();
    });

    it('includes broken links with http status 400 or above', function (): void {
        $page = Page::factory()->withTranslations()->create();

        DB::table('broken_links')->insert([
            'page_id' => $page->id,
            'target_url' => 'https://example.com/gone',
            'http_status' => 404,
            'last_checked_at' => now()->toDateTimeString(),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $result = BuildBrokenLinksQueryAction::run()->get();

        expect($result)->toHaveCount(1)
            ->and($result->first()->target_url)->toBe('https://example.com/gone');
    });

    it('excludes links with http status below 400', function (): void {
        $page = Page::factory()->withTranslations()->create();

        DB::table('broken_links')->insert([
            'page_id' => $page->id,
            'target_url' => 'https://example.com/ok',
            'http_status' => 200,
            'last_checked_at' => now()->toDateTimeString(),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $result = BuildBrokenLinksQueryAction::run()->get();

        expect($result)->toBeEmpty();
    });

    it('eager-loads the page relationship', function (): void {
        $sql = BuildBrokenLinksQueryAction::run()->toSql();

        // Verify the base query targets broken_links
        expect($sql)->toContain('broken_links')
            ->and(BuildBrokenLinksQueryAction::run()->getEagerLoads())->toHaveKey('page');
    });

    it('includes multiple broken links from different status codes above 400', function (): void {
        $page = Page::factory()->withTranslations()->create();

        DB::table('broken_links')->insert([
            [
                'page_id' => $page->id,
                'target_url' => 'https://example.com/not-found',
                'http_status' => 404,
                'last_checked_at' => now()->toDateTimeString(),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
            [
                'page_id' => $page->id,
                'target_url' => 'https://example.com/server-error',
                'http_status' => 500,
                'last_checked_at' => now()->toDateTimeString(),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
        ]);

        $result = BuildBrokenLinksQueryAction::run()->get();

        expect($result)->toHaveCount(2);
    });

    it('limits broken links to assigned sites for non-global users', function (): void {
        $assignedSite = Site::factory()->withTranslations()->create();
        $hiddenSite = Site::factory()->withTranslations()->create();
        $assignedPage = Page::factory()->recycle($assignedSite)->withTranslations()->create();
        $hiddenPage = Page::factory()->recycle($hiddenSite)->withTranslations()->create();

        DB::table('broken_links')->insert([
            [
                'page_id' => $assignedPage->id,
                'target_url' => 'https://example.com/assigned',
                'http_status' => 404,
                'last_checked_at' => now()->toDateTimeString(),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
            [
                'page_id' => $hiddenPage->id,
                'target_url' => 'https://example.com/hidden',
                'http_status' => 404,
                'last_checked_at' => now()->toDateTimeString(),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
        ]);

        test()->actingAs(createScopedUserForBuildBrokenLinksQueryActionTest(collect([$assignedSite->getKey()])));

        expect(BuildBrokenLinksQueryAction::run()->pluck('target_url')->all())
            ->toBe(['https://example.com/assigned']);
    });

    it('denies broken links for non-global users without assigned sites', function (): void {
        $page = Page::factory()->withTranslations()->create();

        DB::table('broken_links')->insert([
            'page_id' => $page->id,
            'target_url' => 'https://example.com/hidden',
            'http_status' => 404,
            'last_checked_at' => now()->toDateTimeString(),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        test()->actingAs(createScopedUserForBuildBrokenLinksQueryActionTest(collect()));

        expect(BuildBrokenLinksQueryAction::run()->get())->toBeEmpty();
    });
});
