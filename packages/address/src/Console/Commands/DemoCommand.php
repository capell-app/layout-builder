<?php

declare(strict_types=1);

namespace Capell\Address\Console\Commands;

use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoCommand extends Command
{
    protected $description = 'Inserts demo address content into the selected site(s).';

    protected $signature = 'capell:address-demo {--sites=}';

    public function handle(): int
    {
        if ($this->option('sites')) {
            $siteOptions = is_string($this->option('sites'))
                ? explode(',', $this->option('sites'))
                : (is_array($this->option('sites')) ? $this->option('sites') : null);
        } else {
            $siteOptions = $this->getDemoSites();
        }

        /** @var class-string<Site> $model */
        $model = Site::class;

        $sites = $model::query()
            ->with(['language', 'languages'])
            ->whereIn('name', $siteOptions)
            ->get();

        if ($sites->isEmpty()) {
            $this->error('Unable to find any sites for: ' . implode(', ', (array) $siteOptions));

            return Command::FAILURE;
        }

        $sites->each(function (Site $site): void {
            $this->newLine();
            $this->line(sprintf('Selected site: %s', $site->name));

            $meta = $site->meta ?? [];

            $address = $this->setupAddress();

            $meta['address_id'] = $address->id;
            $site->meta = $meta;
            $site->save();

            $this->line('Demo address content has been successfully created for site: ' . $site->name);
        });

        $this->newLine();
        $this->info('Address demo content inserted successfully.');

        return Command::SUCCESS;
    }

    private function setupCountry()
    {
        /** @var class-string<Country> $countryModel */
        $countryModel = Country::class;

        /** @var class-string<Language> $model */
        $model = Language::class;

        return $countryModel::query()->firstOrCreate(['iso2' => 'US'], [
            'name' => 'United States',
            'iso2' => 'US',
            'iso3' => 'USA',
            'language_id' => $model::query()->where('code', 'en')->first()->id,
        ]);
    }

    private function setupAddress(): Address
    {
        /** @var class-string<Address> $model */
        $model = Address::class;

        /** @var Address $address */
        $address = $model::query()->firstOrCreate([
            'line1' => '123 Main St',
            'city' => 'Anytown',
            'postal_code' => '12345',
            'country_id' => $this->setupCountry()->id,
        ], [
            'name' => 'Headquarters',
            'line2' => 'Suite 100',
            'state' => 'CA',
            'meta' => [
                'latitude' => 34.0522,
                'longitude' => -118.2437,
            ],
        ]);

        return $address;
    }

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

        if (! Schema::hasTable('sites')) {
            return [config('app.name')];
        }

        return DB::table('sites')
            ->pluck('name')
            ->filter(static fn (mixed $site): bool => is_string($site) && $site !== '')
            ->values()
            ->all();
    }
}
