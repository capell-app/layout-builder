<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Diagnostics\Actions\Dashboard\BuildTailwindBuildStatusAction;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Sleep;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeSitesWithTailwindPaths(int $count): array
{
    $createdSites = [];

    for ($index = 0; $index < $count; $index++) {
        $site = Site::factory()->withTranslations()->create();
        $outputPath = public_path('capell/tailwind/' . $site->id . '/output.css');
        $sourcePath = storage_path('capell/tailwind/' . $site->id . '/classes.txt');

        $createdSites[] = [
            'site' => $site,
            'outputPath' => $outputPath,
            'sourcePath' => $sourcePath,
        ];
    }

    return $createdSites;
}

function writeFile(string $path, string $content = 'x'): void
{
    $dir = dirname($path);
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($path, $content);
}

function cleanupPath(string $path): void
{
    if (file_exists($path)) {
        unlink($path);
    }
}

function createScopedUserForBuildTailwindBuildStatusActionTest(SupportCollection $assignedSiteIds): Authenticatable
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
        'name' => 'Scoped Tailwind Status User',
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
    ]);
    $user->assignedSiteIds = $assignedSiteIds;

    return $user;
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

it('dashboard-dashboard_reports never_built when output file is missing', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $outputPath = public_path('capell/tailwind/' . $site->id . '/output.css');

    // Ensure the output file does not exist
    cleanupPath($outputPath);

    $result = BuildTailwindBuildStatusAction::run();

    $siteRow = $result->sites->toCollection()
        ->first(fn (object $row): bool => $row->siteName === $site->name);

    expect($siteRow)->not->toBeNull()
        ->and($siteRow->status)->toBe('never_built')
        ->and($siteRow->lastBuiltAt)->toBeNull();
});

it('dashboard-dashboard_reports fresh when output is newer than source', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $outputPath = public_path('capell/tailwind/' . $site->id . '/output.css');
    $sourcePath = storage_path('capell/tailwind/' . $site->id . '/classes.txt');

    // Write source first, then output (output is newer)
    writeFile($sourcePath);
    Sleep::sleep(1);
    writeFile($outputPath);

    try {
        $result = BuildTailwindBuildStatusAction::run();

        $siteRow = $result->sites->toCollection()
            ->first(fn (object $row): bool => $row->siteName === $site->name);

        expect($siteRow)->not->toBeNull()
            ->and($siteRow->status)->toBe('fresh')
            ->and($siteRow->lastBuiltAt)->not->toBeNull();
    } finally {
        cleanupPath($outputPath);
        cleanupPath($sourcePath);
    }
});

it('dashboard-dashboard_reports stale when source is newer than output', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $outputPath = public_path('capell/tailwind/' . $site->id . '/output.css');
    $sourcePath = storage_path('capell/tailwind/' . $site->id . '/classes.txt');

    // Write output first, then source (source is newer)
    writeFile($outputPath);
    Sleep::sleep(1);
    writeFile($sourcePath);

    try {
        $result = BuildTailwindBuildStatusAction::run();

        $siteRow = $result->sites->toCollection()
            ->first(fn (object $row): bool => $row->siteName === $site->name);

        expect($siteRow)->not->toBeNull()
            ->and($siteRow->status)->toBe('stale')
            ->and($siteRow->lastBuiltAt)->not->toBeNull();
    } finally {
        cleanupPath($outputPath);
        cleanupPath($sourcePath);
    }
});

it('returns one row per site', function (): void {
    // Clear any existing sites so we get a known count
    Site::factory()->withTranslations()->count(3)->create();

    $result = BuildTailwindBuildStatusAction::run();

    expect($result->sites->count())->toBe(Site::query()->count());
});

it('limits rows to assigned sites for non-global users', function (): void {
    $assignedSite = Site::factory()->withTranslations()->create(['name' => 'Assigned Site']);
    Site::factory()->withTranslations()->create(['name' => 'Hidden Site']);

    test()->actingAs(createScopedUserForBuildTailwindBuildStatusActionTest(collect([$assignedSite->getKey()])));

    $siteNames = BuildTailwindBuildStatusAction::run()
        ->sites
        ->toCollection()
        ->pluck('siteName')
        ->all();

    expect($siteNames)->toBe(['Assigned Site']);
});

it('denies rows for non-global users without assigned sites', function (): void {
    Site::factory()->withTranslations()->count(2)->create();

    test()->actingAs(createScopedUserForBuildTailwindBuildStatusActionTest(collect()));

    expect(BuildTailwindBuildStatusAction::run()->sites)->toHaveCount(0);
});

it('aggregates summary counts correctly', function (): void {
    // Create 3 sites: 1 fresh, 1 stale, 1 never_built
    $freshSite = Site::factory()->withTranslations()->create();
    $staleSite = Site::factory()->withTranslations()->create();
    $neverSite = Site::factory()->withTranslations()->create();

    $freshOutput = public_path('capell/tailwind/' . $freshSite->id . '/output.css');
    $freshSource = storage_path('capell/tailwind/' . $freshSite->id . '/classes.txt');
    $staleOutput = public_path('capell/tailwind/' . $staleSite->id . '/output.css');
    $staleSource = storage_path('capell/tailwind/' . $staleSite->id . '/classes.txt');
    $neverOutput = public_path('capell/tailwind/' . $neverSite->id . '/output.css');

    cleanupPath($neverOutput);

    // Fresh: output newer than source
    writeFile($freshSource);
    Sleep::sleep(1);
    writeFile($freshOutput);

    // Stale: source newer than output
    writeFile($staleOutput);
    Sleep::sleep(1);
    writeFile($staleSource);

    try {
        $result = BuildTailwindBuildStatusAction::run();

        expect($result->freshCount)->toBeGreaterThanOrEqual(1)
            ->and($result->staleCount)->toBeGreaterThanOrEqual(1)
            ->and($result->neverBuiltCount)->toBeGreaterThanOrEqual(1);

        // Totals must add up to site count
        expect($result->freshCount + $result->staleCount + $result->neverBuiltCount)
            ->toBe(Site::query()->count());
    } finally {
        cleanupPath($freshOutput);
        cleanupPath($freshSource);
        cleanupPath($staleOutput);
        cleanupPath($staleSource);
    }
});
