<?php

declare(strict_types=1);

namespace Capell\PasswordPolicy\Tests;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Core\Facades\CapellCore;
use Capell\PasswordPolicy\Providers\PasswordPolicyServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;

abstract class PasswordPolicyTestCase extends AbstractTestCase
{
    use CreatesAdminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->registerAndMigrateSettings(
            CapellCore::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/core/database/settings',
        );

        $this->registerAndMigrateSettings(
            CapellAdmin::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/admin/database/settings',
        );

        $this->registerAndMigrateSettings(
            ['create_password_policy_settings'],
            __DIR__ . '/../database/settings',
        );
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-password-policy';
    }

    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AdminServiceProvider::class,
            AdminPanelProvider::class,
            LivewireServiceProvider::class,
            PasswordPolicyServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(PasswordPolicyServiceProvider::$packageName);
    }

    #[Override]
    protected function registerPackageConfigs(Application $app, ?array $packages = null): void
    {
        parent::registerPackageConfigs($app, $packages);

        $this->registerPublishConfig('admin');
        $this->registerPublishConfig('password-policy');
    }
}
