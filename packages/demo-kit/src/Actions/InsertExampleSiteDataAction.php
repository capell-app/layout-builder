<?php

declare(strict_types=1);

namespace Capell\DemoKit\Actions;

use Capell\Core\Facades\CapellCore;
use Capell\DemoKit\Providers\DemoKitServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

final class InsertExampleSiteDataAction
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $package = CapellCore::getPackage(DemoKitServiceProvider::$packageName);
        $demoCommand = $package->getDemoCommand();

        if ($demoCommand === null) {
            throw new RuntimeException((string) __('capell-demo-kit::actions.example_site_data_command_missing'));
        }

        Artisan::call($demoCommand, $this->commandParams($data));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function commandParams(array $data): array
    {
        $params = [
            '--force' => true,
        ];

        foreach (['url', 'user', 'languages', 'sites'] as $param) {
            if (! array_key_exists($param, $data)) {
                continue;
            }

            if ($data[$param] === null) {
                continue;
            }

            if ($data[$param] === '') {
                continue;
            }

            if ($data[$param] === []) {
                continue;
            }

            $params['--' . $param] = $data[$param];
        }

        return $params;
    }
}
