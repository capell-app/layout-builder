<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Tests;

use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\ThemeStudio\Admin\ThemeStudioAdminServiceProvider;
use Capell\ThemeStudio\Core\ThemeStudioCoreServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;

class ThemeStudioAdminTestCase extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAndMigrateSettings(
            ['create_theme_studio_settings'],
            __DIR__ . '/../../core/database/settings',
        );
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-theme-studio-admin';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AdminServiceProvider::class,
            ThemeStudioCoreServiceProvider::class,
            ThemeStudioAdminServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }
}
