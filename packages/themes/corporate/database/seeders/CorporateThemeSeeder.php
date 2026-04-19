<?php

declare(strict_types=1);

namespace Capell\Themes\Corporate\Database\Seeders;

use Capell\Themes\Corporate\Actions\InstallCorporateThemeAction;
use Illuminate\Database\Seeder;

class CorporateThemeSeeder extends Seeder
{
    public function run(): void
    {
        (new InstallCorporateThemeAction)->handle([
            'force' => false,
            'seed_layouts' => class_exists('Capell\\Mosaic\\Models\\Layout'),
        ]);
    }
}
