<?php

declare(strict_types=1);

namespace Capell\Address\Commands;

use Capell\Address\Enums\ModelEnum as AddressModelEnum;
use Capell\Address\Models\Address;
use Capell\Core\Commands\Concerns\HasSitesOption;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Illuminate\Console\Command;

class DemoCommand extends Command
{
    use HasSitesOption;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inserts demo address content into the selected site(s).';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capell-address:demo {--sites=}';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('sites')) {
            $siteOptions = is_string($this->option('sites'))
                ? explode(',', $this->option('sites'))
                : (is_array($this->option('sites')) ? $this->option('sites') : null);
        } else {
            $siteOptions = $this->getSelectedSites();
        }

        $sites = CapellCore::getModel(ModelEnum::Site)::query()->with(['language', 'languages'])->whereIn('name', $siteOptions)->get();

        if ($sites->isEmpty()) {
            $this->error('Unable to find any sites for: ' . implode(', ', (array) $siteOptions));

            return Command::FAILURE;
        }

        foreach ($sites as $site) {
            $this->newLine();
            $this->line(sprintf('Selected site: %s', $site->name));

            $meta = $site->meta ?? [];

            $address = $this->setupAddress();

            $meta['address_id'] = $address->id;

            $site->update(['meta' => $meta]);

            $this->line('Demo address content has been successfully created for site: ' . $site->name);
        }

        $this->line('Hero demo content inserted successfully.');

        return Command::SUCCESS;
    }

    private function setupCountry()
    {
        $countryModel = CapellCore::getModel(AddressModelEnum::Country);

        return $countryModel::firstOrCreate(
            ['iso2' => 'US'],
            [
                'name' => 'United States',
                'iso2' => 'US',
                'iso3' => 'USA',
                'language_id' => CapellCore::getModel(ModelEnum::Language)::where('code', 'en')->first()->id,
            ],
        );
    }

    private function setupAddress(): Address
    {
        return CapellCore::getModel(AddressModelEnum::Address)::firstOrCreate(
            [
                'line1' => '123 Main St',
                'city' => 'Anytown',
                'postal_code' => '12345',
                'country_id' => $this->setupCountry()->id,
            ],
            [
                'name' => 'Headquarters',
                'line2' => 'Suite 100',
                'state' => 'CA',
                'meta' => [
                    'latitude' => 34.0522,
                    'longitude' => -118.2437,
                ],
            ],
        );
    }
}
