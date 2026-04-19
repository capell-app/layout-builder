<?php

declare(strict_types=1);

namespace Capell\Themes\Saas\Database\Seeders;

use Capell\Themes\Saas\Actions\InstallSaasThemeAction;
use Illuminate\Database\Seeder;

class SaasThemeSeeder extends Seeder
{
    public function run(): void
    {
        (new InstallSaasThemeAction)->handle([
            'force' => false,
            'seed_layouts' => class_exists('Capell\\Mosaic\\Models\\Layout'),
        ]);
    }
}
