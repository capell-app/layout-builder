<?php

declare(strict_types=1);

namespace Capell\Notes\Tests;

use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Notes\Providers\NotesServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'Capell\\Notes\\Database\\Factories\\' => __DIR__ . '/../database/factories/',
        'Capell\\Notes\\' => __DIR__ . '/../src/',
    ];

    foreach ($prefixes as $prefix => $basePath) {
        if (! str_starts_with($class, $prefix)) {
            continue;
        }

        $path = $basePath . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

        if (file_exists($path)) {
            require_once $path;
        }
    }
});

class NotesTestCase extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAndMigrateSettings(
            CapellCore::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/core/database/settings',
        );
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-notes';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            NotesServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(NotesServiceProvider::$packageName);
    }
}
