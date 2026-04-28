<?php

declare(strict_types=1);

namespace Capell\Plugins\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Plugins\Jobs\ValidateLicensesJob;
use Capell\Plugins\Services\AnystackClient;
use Capell\Plugins\Services\ComposerRunner;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\LaravelPackageTools\Package;

class PluginsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-plugins';

    public static string $packageName = 'capell-app/plugins';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasConfigFile()
            ->hasMigrations([
                '2026_01_01_000001_create_marketplace_plugins_table',
                '2026_01_01_000002_create_marketplace_plugin_licenses_table',
                '2026_01_01_000003_create_marketplace_plugin_audit_log_table',
            ]);
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(AnystackClient::class, function (): AnystackClient {
            $baseUrlRaw = config('capell-plugins.anystack.base_url', 'https://api.anystack.sh');
            $apiKeyRaw = config('capell-plugins.anystack.api_key');
            $timeoutRaw = config('capell-plugins.anystack.timeout_seconds', 10);

            return new AnystackClient(
                baseUrl: is_string($baseUrlRaw) ? $baseUrlRaw : 'https://api.anystack.sh',
                apiKey: is_string($apiKeyRaw) && $apiKeyRaw !== '' ? $apiKeyRaw : null,
                timeoutSeconds: is_int($timeoutRaw) ? $timeoutRaw : 10,
            );
        });

        $this->app->singleton(ComposerRunner::class, function (): ComposerRunner {
            $binaryRaw = config('capell-plugins.composer.binary', 'composer');
            $timeoutRaw = config('capell-plugins.composer.timeout_seconds', 600);

            return new ComposerRunner(
                binary: is_string($binaryRaw) ? $binaryRaw : 'composer',
                timeoutSeconds: is_int($timeoutRaw) ? $timeoutRaw : 600,
                workingDirectory: base_path(),
            );
        });
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'capell-plugins');

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->job(ValidateLicensesJob::class)->dailyAt('03:17');
        });
    }

    public function registeringPackage(): void
    {
        $this->registerPackageMetadata();
    }

    private function registerPackageMetadata(): void
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            description: fn (): string => __('capell-plugins::package.description'),
        );
    }
}
