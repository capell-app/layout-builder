<?php

declare(strict_types=1);

namespace Capell\Forms\Providers;

use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

class FormsServiceProvider extends AbstractPackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('capell-forms')
            ->hasMigrations([
                'create_forms_table',
                'create_submissions_table',
            ]);
    }
}
