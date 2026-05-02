<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Actions\Dashboard;

use Capell\Admin\Support\SiteScope;
use Capell\Core\Models\Site;
use Capell\DeveloperTools\Data\Dashboard\TailwindBuildStatusData;
use Capell\DeveloperTools\Data\Dashboard\TailwindSiteStatusData;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\LaravelData\DataCollection;

/**
 * @method static TailwindBuildStatusData run()
 */
final class BuildTailwindBuildStatusAction
{
    use AsAction;

    public function handle(): TailwindBuildStatusData
    {
        $sites = SiteScope::applyForCurrentActor(Site::query(), 'id')
            ->orderBy('name')
            ->get();

        $rows = [];
        $freshCount = 0;
        $staleCount = 0;
        $neverBuiltCount = 0;

        foreach ($sites as $site) {
            if (! $site instanceof Site) {
                continue;
            }

            $outputPath = $this->outputPath($site->id);
            $sourcePath = $this->sourcePath($site->id);

            if (! File::exists($outputPath)) {
                $rows[] = new TailwindSiteStatusData(
                    siteName: $site->name,
                    status: 'never_built',
                    lastBuiltAt: null,
                );
                $neverBuiltCount++;

                continue;
            }

            $outputModified = File::lastModified($outputPath);
            $lastBuiltAt = Date::createFromTimestamp($outputModified)->format('c');

            if (File::exists($sourcePath) && File::lastModified($sourcePath) > $outputModified) {
                $rows[] = new TailwindSiteStatusData(
                    siteName: $site->name,
                    status: 'stale',
                    lastBuiltAt: $lastBuiltAt,
                );
                $staleCount++;
            } else {
                $rows[] = new TailwindSiteStatusData(
                    siteName: $site->name,
                    status: 'fresh',
                    lastBuiltAt: $lastBuiltAt,
                );
                $freshCount++;
            }
        }

        return new TailwindBuildStatusData(
            sites: TailwindSiteStatusData::collect($rows, DataCollection::class),
            freshCount: $freshCount,
            staleCount: $staleCount,
            neverBuiltCount: $neverBuiltCount,
        );
    }

    private function outputPath(int $siteId): string
    {
        return public_path('capell/tailwind/' . $siteId . '/output.css');
    }

    private function sourcePath(int $siteId): string
    {
        return storage_path('capell/tailwind/' . $siteId . '/classes.txt');
    }
}
