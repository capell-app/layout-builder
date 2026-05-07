<?php

declare(strict_types=1);

namespace Capell\DemoKit\Console\Commands\Concerns;

use Capell\Core\Console\Commands\Concerns\PromptsWithOptionFallback;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\multiselect;

/**
 * @mixin Command
 */
trait HasSitesOption
{
    use PromptsWithOptionFallback;

    /**
     * @return array<int, string>
     */
    private function getDemoSites(): array
    {
        $sitesOption = $this->option('sites');
        if (is_string($sitesOption) && $sitesOption !== '') {
            return array_values(array_filter(
                array_map(trim(...), explode(',', $sitesOption)),
                static fn (string $site): bool => $site !== '',
            ));
        }

        $demoData = collect(config('capell-demo-kit.pages'));

        $databaseSites = Schema::hasTable('sites')
            ? DB::table('sites')->pluck('name')->toArray()
            : [];

        $demoSitesOptions = collect([config('app.name')])
            ->merge($databaseSites)
            ->merge($demoData->map(fn (array $demoSite): string => $demoSite['name']['en']))
            ->unique()
            ->mapWithKeys(fn (string $site): array => [$site => $site])
            ->all();

        $this->requireInteractiveOrFail('Example sites', 'Pass --sites=<comma,separated,list>.');

        return multiselect(
            label: 'Choose the example site content?',
            options: $demoSitesOptions,
            default: $databaseSites,
            required: true,
        );
    }
}
