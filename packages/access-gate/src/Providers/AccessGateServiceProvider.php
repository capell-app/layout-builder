<?php

declare(strict_types=1);

namespace Capell\AccessGate\Providers;

use Capell\AccessGate\Console\Commands\AccessGateDoctorCommand;
use Capell\AccessGate\Console\Commands\AccessGateInstallCommand;
use Capell\AccessGate\Console\Commands\AccessGateSetupCommand;
use Capell\AccessGate\Enums\ResourceEnum;
use Capell\AccessGate\Frontend\Rules\AccessGateAreaStatusCondition;
use Capell\AccessGate\Frontend\Rules\AccessGateRegistrationStatusCondition;
use Capell\AccessGate\Frontend\Rules\HasActiveAccessGateGrantCondition;
use Capell\AccessGate\Frontend\Rules\MissingActiveAccessGateGrantCondition;
use Capell\AccessGate\Http\Middleware\AccessGateMiddleware;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\BrowserToken;
use Capell\AccessGate\Models\ClaimToken;
use Capell\AccessGate\Models\Event;
use Capell\AccessGate\Models\Grant;
use Capell\AccessGate\Models\Registration;
use Capell\AccessGate\Support\AccessRequestMethodRegistry;
use Capell\AccessGate\Support\RegistrationFieldRegistry;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Frontend\Support\Rules\FrontendRuleConditionRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
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
            ->hasViews(self::$name)
            ->hasRoute('web')
            ->hasCommands([
                AccessGateDoctorCommand::class,
                AccessGateInstallCommand::class,
                AccessGateSetupCommand::class,
            ])
            ->hasMigrations([
                '2026_05_08_000001_create_access_gate_areas_table',
                '2026_05_08_000002_create_access_gate_registrations_table',
                '2026_05_08_000003_create_access_gate_grants_table',
                '2026_05_08_000004_create_access_gate_claim_tokens_table',
                '2026_05_08_000005_create_access_gate_browser_tokens_table',
                '2026_05_08_000006_create_access_gate_events_table',
                '2026_05_08_000007_add_site_id_to_access_gate_areas_table',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RegistrationFieldRegistry::class);
        $this->app->singleton(AccessRequestMethodRegistry::class);
        $this->registerMiddlewareAliases();
        $this->registerMiddlewarePriority();

        $this->registerPackageMetadata();

        $this->app->booted(function (): void {
            $this->registerRateLimiters();
            $this->registerConfiguredRegistrationFields();
            $this->registerConfiguredAccessRequestMethods();

            if (! $this->hasCapellCore()) {
                return;
            }

            if (! $this->isPackageInstalled()) {
                return;
            }

            $this
                ->registerModels()
                ->registerAdminResources()
                ->registerFrontendRuleConditions()
                ->registerProtectedTables();
        });
    }

    public function packageBooted(): void
    {
        if (! $this->hasCapellCore()) {
            return;
        }

        if (! $this->isPackageInstalled()) {
            return;
        }

        Relation::morphMap([
            'area' => Area::class,
            'registration' => Registration::class,
            'grant' => Grant::class,
            'claim_token' => ClaimToken::class,
            'browser_token' => BrowserToken::class,
            'event' => Event::class,
        ], merge: true);
    }

    private function registerFrontendRuleConditions(): self
    {
        if (! class_exists(FrontendRuleConditionRegistry::class)) {
            return $this;
        }

        $this->app->afterResolving(FrontendRuleConditionRegistry::class, function (FrontendRuleConditionRegistry $registry): void {
            $registry->register(AccessGateAreaStatusCondition::class);
            $registry->register(AccessGateRegistrationStatusCondition::class);
            $registry->register(HasActiveAccessGateGrantCondition::class);
            $registry->register(MissingActiveAccessGateGrantCondition::class);
        });

        return $this;
    }

    private function registerConfiguredAccessRequestMethods(): self
    {
        $methods = config('access-gate.registration.identity_methods', []);

        if (! is_array($methods)) {
            return $this;
        }

        $registry = $this->app->make(AccessRequestMethodRegistry::class);

        foreach ($methods as $method) {
            if (! is_string($method)) {
                continue;
            }

            $registry->register($method);
        }

        return $this;
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

    private function registerMiddlewareAliases(): self
    {
        Route::aliasMiddleware('access-gate', AccessGateMiddleware::class);

        return $this;
    }

    private function registerRateLimiters(): self
    {
        RateLimiter::for('access-gate-request', function (Request $request): Limit {
            $email = Str::lower((string) $request->input('email', ''));
            $area = (string) $request->route('area', '');
            $key = hash('sha256', $area . '|' . $email . '|' . $request->ip());

            return Limit::perMinute(6)->by($key);
        });

        return $this;
    }

    private function registerMiddlewarePriority(): self
    {
        $this->applyMiddlewarePriority($this->app->make(Router::class));

        $this->app->afterResolving(Router::class, function (Router $router): void {
            $this->applyMiddlewarePriority($router);
        });

        return $this;
    }

    private function applyMiddlewarePriority(Router $router): void
    {
        $priority = [
            AccessGateMiddleware::class,
            'access-gate',
            ...$this->pageCacheMiddlewarePriorityNames($router),
        ];

        $orderedPriority = collect($priority)
            ->merge($this->existingMiddlewarePriority($router))
            ->unique()
            ->values()
            ->all();

        $router->middlewarePriority = $orderedPriority;

        if (! $this->app->bound(HttpKernel::class)) {
            return;
        }

        $kernel = $this->app->make(HttpKernel::class);

        if (method_exists($kernel, 'setMiddlewarePriority')) {
            $kernel->setMiddlewarePriority($orderedPriority);
        }
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(static::$packageName);
    }

    private function hasCapellCore(): bool
    {
        return class_exists(CapellCore::class);
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
            installCommand: 'capell:access-gate-install',
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

    /**
     * @return list<string>
     */
    private function pageCacheAliases(): array
    {
        $aliases = config('access-gate.middleware.page_cache_aliases', []);

        if (! is_array($aliases)) {
            return [];
        }

        return collect($aliases)
            ->filter(fn (mixed $alias): bool => is_string($alias) && $alias !== '')
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function pageCacheMiddlewarePriorityNames(Router $router): array
    {
        $registeredMiddleware = $router->getMiddleware();

        return collect($this->pageCacheAliases())
            ->flatMap(fn (string $alias): array => array_values(array_filter([
                $alias,
                $registeredMiddleware[$alias] ?? null,
            ], is_string(...))))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function existingMiddlewarePriority(Router $router): array
    {
        if (! $this->app->bound(HttpKernel::class)) {
            return $router->middlewarePriority;
        }

        $kernel = $this->app->make(HttpKernel::class);

        if (! method_exists($kernel, 'getMiddlewarePriority')) {
            return $router->middlewarePriority;
        }

        return $kernel->getMiddlewarePriority();
    }
}
