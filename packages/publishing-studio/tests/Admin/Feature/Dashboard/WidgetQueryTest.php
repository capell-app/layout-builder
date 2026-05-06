<?php

declare(strict_types=1);

use Capell\Diagnostics\Actions\Dashboard\BuildConfigDriftAction;
use Capell\Diagnostics\Actions\Dashboard\BuildMigrationsHealthAction;
use Capell\Diagnostics\Actions\Dashboard\BuildPackagesInstalledAction;
use Capell\Diagnostics\Actions\Dashboard\BuildRegistryHealthAction;
use Capell\Diagnostics\Actions\Dashboard\BuildTailwindBuildStatusAction;
use Capell\PublishingStudio\Actions\Dashboard\BuildWorkspaceMergeHistoryAction;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\DataCollection;

describe('BuildRegistryHealthAction', function (): void {
    it('returns registry health data with sections', function (): void {
        $data = BuildRegistryHealthAction::run();

        expect($data)->toHaveProperty('sections');
        expect($data->sections)->not->toBeEmpty();

        foreach ($data->sections as $section) {
            expect($section)->toHaveProperty('name');
            expect($section)->toHaveProperty('count');
            expect($section)->toHaveProperty('entries');
            expect($section->count)->toBeGreaterThanOrEqual(0);
        }
    });

    it('detects page types and schemas sections', function (): void {
        $data = BuildRegistryHealthAction::run();
        $sectionNames = [];
        foreach ($data->sections as $section) {
            $sectionNames[] = $section->name;
        }

        expect($sectionNames)->toContain('Page types');
        expect($sectionNames)->toContain('Settings schemas');
    });
});

describe('BuildMigrationsHealthAction', function (): void {
    it('returns migration health structure with counts', function (): void {
        $data = BuildMigrationsHealthAction::run();

        expect($data)->toHaveProperty('pendingCount');
        expect($data)->toHaveProperty('orphanedCount');
        expect($data)->toHaveProperty('pendingMigrations');
        expect($data)->toHaveProperty('orphanedRegistrations');
        expect($data)->toHaveProperty('lastBatch');

        expect($data->pendingCount)->toBeInt();
        expect($data->orphanedCount)->toBeInt();
        expect($data->pendingMigrations)->toBeArray();
        expect($data->orphanedRegistrations)->toBeArray();
    });

    it('last batch matches database maximum', function (): void {
        $data = BuildMigrationsHealthAction::run();

        if (DB::table('migrations')->count() > 0) {
            $maxBatch = DB::table('migrations')->max('batch');
            expect($data->lastBatch)->toBe((int) $maxBatch);
        } else {
            expect($data->lastBatch)->toBeNull();
        }
    });
});

describe('BuildPackagesInstalledAction', function (): void {
    it('returns packages data collection', function (): void {
        $data = BuildPackagesInstalledAction::run();

        expect($data)->toHaveProperty('packages');
        expect($data->packages)->toBeInstanceOf(DataCollection::class);
    });

    it('each package has required fields', function (): void {
        $data = BuildPackagesInstalledAction::run();

        if (count($data->packages) > 0) {
            foreach ($data->packages as $package) {
                expect($package)->toHaveProperty('name');
                expect($package)->toHaveProperty('composerName');
                expect($package)->toHaveProperty('version');
                expect($package)->toHaveProperty('configPublished');
            }
        } else {
            expect(count($data->packages))->toBe(0);
        }
    });
});

describe('BuildConfigDriftAction', function (): void {
    it('returns config drift data with entries and counts', function (): void {
        $data = BuildConfigDriftAction::run();

        expect($data)->toHaveProperty('entries');
        expect($data)->toHaveProperty('totalDriftCount');
        expect($data)->toHaveProperty('packagesChecked');

        expect($data->totalDriftCount)->toBeInt();
        expect($data->packagesChecked)->toBeInt();
        expect($data->entries)->toBeInstanceOf(DataCollection::class);
    });

    it('drift counts are non-negative', function (): void {
        $data = BuildConfigDriftAction::run();
        expect($data->totalDriftCount)->toBeGreaterThanOrEqual(0);
        expect($data->packagesChecked)->toBeGreaterThanOrEqual(0);
    });
});

describe('BuildTailwindBuildStatusAction', function (): void {
    it('returns tailwind build status with site collection and counts', function (): void {
        $data = BuildTailwindBuildStatusAction::run();

        expect($data)->toHaveProperty('sites');
        expect($data)->toHaveProperty('freshCount');
        expect($data)->toHaveProperty('staleCount');
        expect($data)->toHaveProperty('neverBuiltCount');

        expect($data->sites)->toBeInstanceOf(DataCollection::class);
    });

    it('build counts are non-negative integers', function (): void {
        $data = BuildTailwindBuildStatusAction::run();

        expect($data->freshCount)->toBeInt()->toBeGreaterThanOrEqual(0);
        expect($data->staleCount)->toBeInt()->toBeGreaterThanOrEqual(0);
        expect($data->neverBuiltCount)->toBeInt()->toBeGreaterThanOrEqual(0);
    });
});

describe('BuildWorkspaceMergeHistoryAction', function (): void {
    it('returns merge history entries collection', function (): void {
        $data = BuildWorkspaceMergeHistoryAction::run();

        expect($data)->toHaveProperty('entries');
        expect($data->entries)->toBeInstanceOf(DataCollection::class);
    });

    it('handles empty merge history gracefully', function (): void {
        $data = BuildWorkspaceMergeHistoryAction::run();

        expect(count($data->entries))->toBeGreaterThanOrEqual(0);

        foreach ($data->entries as $entry) {
            expect($entry)->toHaveProperty('id');
            expect($entry)->toHaveProperty('name');
            expect($entry)->toHaveProperty('actorName');
        }
    });
});
