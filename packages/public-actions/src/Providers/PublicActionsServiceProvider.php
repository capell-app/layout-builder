<?php

declare(strict_types=1);

namespace Capell\PublicActions\Providers;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\PublicActions\Enums\ResourceEnum;
use Capell\PublicActions\Listeners\SubmitPublicActionFromFormSubmission;
use Capell\PublicActions\Models\PublicAction;
use Capell\PublicActions\Models\PublicActionDestination;
use Capell\PublicActions\Models\PublicActionDispatchAttempt;
use Capell\PublicActions\Models\PublicActionIntegrationToken;
use Capell\PublicActions\Models\PublicActionSubmission;
use Capell\PublicActions\Support\Providers\HttpWebhookPublicActionAdapter;
use Capell\PublicActions\Support\PublicActionDestinationAdapterRegistry;
use Capell\PublicActions\Support\PublicActionHandlerRegistry;
use Capell\PublicActions\Support\PublicActionProviderPresetRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Spatie\LaravelPackageTools\Package;

class PublicActionsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-public-actions';

    public static string $packageName = 'capell-app/public-actions';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-public-actions')
            ->hasTranslations()
            ->hasViews(self::$name)
            ->hasRoute('web')
            ->hasMigrations([
                '01_create_public_actions_table',
                '02_create_public_action_destinations_table',
                '03_create_public_action_submissions_table',
                '04_create_public_action_dispatch_attempts_table',
                '05_create_public_action_integration_tokens_table',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->registerPackageMetadata();
        $this->app->singleton(PublicActionHandlerRegistry::class);
        $this->app->singleton(PublicActionProviderPresetRegistry::class);
        $this->app->singleton(PublicActionDestinationAdapterRegistry::class, static function (): PublicActionDestinationAdapterRegistry {
            $registry = new PublicActionDestinationAdapterRegistry;
            $registry->register('http_webhook', HttpWebhookPublicActionAdapter::class);

            return $registry;
        });
        $this->registerBladeComponents();

        $this->app->booted(function (): void {
            $this->registerRateLimiters();

            if (! $this->isPackageInstalled()) {
                return;
            }

            $this
                ->registerModels()
                ->registerAdminResources()
                ->registerListeners()
                ->registerProtectedTables();
        });
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(self::$packageName);
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-public-actions::package.description'),
        );

        return $this;
    }

    private function registerBladeComponents(): self
    {
        $this->callAfterResolving(BladeCompiler::class, static function (BladeCompiler $blade): void {
            $blade->anonymousComponentNamespace('capell-public-actions::components', 'capell-public-actions');
        });

        return $this;
    }

    private function registerModels(): self
    {
        CapellCore::registerModels([
            PublicAction::class,
            PublicActionDestination::class,
            PublicActionSubmission::class,
            PublicActionDispatchAttempt::class,
            PublicActionIntegrationToken::class,
        ]);

        return $this;
    }

    private function registerAdminResources(): self
    {
        if (! class_exists(CapellAdmin::class) || ! class_exists(AdminSurfaceContributionData::class)) {
            return $this;
        }

        foreach (ResourceEnum::cases() as $resource) {
            CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
                class: $resource->value,
                group: $resource->name,
            ));
        }

        return $this;
    }

    private function registerRateLimiters(): self
    {
        RateLimiter::for('public-actions-submit', function (Request $request): Limit {
            $action = (string) $request->route('action', '');
            $email = Str::lower((string) $request->input('email', ''));
            $key = hash('sha256', $action . '|' . $email . '|' . $request->ip());

            return Limit::perMinute(12)->by($key);
        });

        RateLimiter::for('public-actions-api', fn (Request $request): Limit => Limit::perMinute(120)->by((string) $request->ip()));

        return $this;
    }

    private function registerProtectedTables(): self
    {
        $tables = config('capell-public-actions.tables', []);

        if (! is_array($tables)) {
            return $this;
        }

        foreach ($tables as $tableName) {
            if (! is_string($tableName)) {
                continue;
            }

            if ($tableName === '') {
                continue;
            }

            CapellCore::registerProtectedTable(static fn (): string => $tableName);
        }

        return $this;
    }

    private function registerListeners(): self
    {
        $formSubmittedEvent = implode('\\', ['Capell', 'FormBuilder', 'Events', 'FormSubmitted']);

        if (class_exists($formSubmittedEvent)) {
            Event::listen($formSubmittedEvent, SubmitPublicActionFromFormSubmission::class);
        }

        return $this;
    }
}
