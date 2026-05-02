<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Tests;

use Capell\Tests\AbstractTestCase;
use Capell\ThemeStudio\Core\ThemeStudioCoreServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;

class ThemeStudioCoreTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-theme-studio-core';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            ThemeStudioCoreServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }
}
