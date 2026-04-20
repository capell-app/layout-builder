<?php

declare(strict_types=1);

namespace Capell\Tests\Assistant;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Assistant\Providers\AssistantServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Tests\AbstractTestCase;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;
use Prism\Prism\PrismServiceProvider;

class AssistantTestCase extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAndMigrateSettings(
            CapellCore::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/core/database/settings',
        );
        $this->registerAndMigrateSettings(
            CapellAdmin::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/admin/database/settings',
        );
        $this->registerAndMigrateSettings(
            [
                'create_assistant_settings',
                '2026_04_18_000001_update_assistant_settings_add_ai_creator',
            ],
            __DIR__ . '/../../../packages/assistant/database/settings',
        );
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-assistant';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AssistantServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
            LivewireServiceProvider::class,
            PrismServiceProvider::class,
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
        CapellCore::forcePackageInstalled(AssistantServiceProvider::$packageName);
    }
}
