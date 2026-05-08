<?php

declare(strict_types=1);

namespace Capell\AccessGate\Providers;

use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\BrowserToken;
use Capell\AccessGate\Models\ClaimToken;
use Capell\AccessGate\Models\Event;
use Capell\AccessGate\Models\Grant;
use Capell\AccessGate\Models\Registration;
use Capell\AccessGate\Support\RegistrationFieldRegistry;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\CapellCoreManager;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

class AccessGateServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-access-gate';

    public static string $packageName = 'capell-app/access-gate';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('access-gate')
            ->hasTranslations()
            ->hasMigrations([
                '2026_05_08_000001_create_access_gate_areas_table',
                '2026_05_08_000002_create_access_gate_registrations_table',
                '2026_05_08_000003_create_access_gate_grants_table',
                '2026_05_08_000004_create_access_gate_claim_tokens_table',
                '2026_05_08_000005_create_access_gate_browser_tokens_table',
                '2026_05_08_000006_create_access_gate_events_table',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RegistrationFieldRegistry::class);

        $this->registerPackageMetadata();

        $this->app->booted(function (): void {
            $this->registerConfiguredRegistrationFields();

            if (! $this->hasCapellCore()) {
                return;
            }

            if (! $this->isPackageInstalled()) {
                return;
            }

            $this
                ->registerModels()
                ->registerProtectedTables();
        });
    }

    private function registerConfiguredRegistrationFields(): self
    {
        $fields = config('access-gate.registration.fields', []);

        if (! is_array($fields)) {
            return $this;
        }

        $registry = $this->app->make(RegistrationFieldRegistry::class);

        foreach ($fields as $field) {
            if (! is_string($field)) {
                continue;
            }

            $registry->register($field);
        }

        return $this;
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(static::$packageName);
    }

    private function hasCapellCore(): bool
    {
        return class_exists(CapellCore::class) && $this->app->bound(CapellCoreManager::class);
    }

    private function registerPackageMetadata(): self
    {
        if (! $this->hasCapellCore()) {
            return $this;
        }

        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(static::$packageName),
            description: fn (): string => __('capell-access-gate::package.description'),
        );

        return $this;
    }

    private function registerModels(): self
    {
        CapellCore::registerModels([
            Area::class,
            Registration::class,
            Grant::class,
            ClaimToken::class,
            BrowserToken::class,
            Event::class,
        ]);

        return $this;
    }

    private function registerProtectedTables(): self
    {
        foreach ($this->protectedTables() as $tableName) {
            CapellCore::registerProtectedTable(fn (): string => $tableName);
        }

        return $this;
    }

    /**
     * @return list<string>
     */
    private function protectedTables(): array
    {
        return [
            'access_gate_areas',
            'access_gate_registrations',
            'access_gate_grants',
            'access_gate_claim_tokens',
            'access_gate_browser_tokens',
            'access_gate_events',
        ];
    }
}
