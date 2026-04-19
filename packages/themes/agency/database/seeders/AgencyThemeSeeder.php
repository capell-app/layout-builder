<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\Database\Seeders;

use Capell\Themes\Agency\Actions\InstallAgencyThemeAction;
use Illuminate\Database\Seeder;

class AgencyThemeSeeder extends Seeder
{
    public function run(): void
    {
        (new InstallAgencyThemeAction)->handle([
            'force' => false,
            'seed_layouts' => class_exists('Capell\\Mosaic\\Models\\Layout'),
        ]);
    }
}
