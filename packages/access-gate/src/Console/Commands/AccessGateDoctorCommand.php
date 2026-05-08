<?php

declare(strict_types=1);

namespace Capell\AccessGate\Console\Commands;

use Capell\AccessGate\Models\Area;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class AccessGateDoctorCommand extends Command
{
    protected $signature = 'capell:access-gate-doctor';

    protected $description = 'Check Access Gate configuration and safety requirements.';

    public function handle(Router $router): int
    {
        $failures = 0;

        $failures += $this->checkDatabase();
        $failures += $this->checkMiddleware($router);
        $failures += $this->checkCookies();
        $this->checkClaimHosts();

        if ($failures > 0) {
            $this->error(__('capell-access-gate::doctor.failed', ['count' => $failures]));

            return self::FAILURE;
        }

        $this->info(__('capell-access-gate::doctor.passed'));

        return self::SUCCESS;
    }

    private function checkDatabase(): int
    {
        $connection = config('access-gate.connection');
        $connectionName = is_string($connection) && $connection !== '' ? $connection : config('database.default');

        try {
            DB::connection($connectionName)->getPdo();
        } catch (Throwable $exception) {
            $this->error(__('capell-access-gate::doctor.database.unreachable', ['connection' => $connectionName]));

            return 1;
        }

        $missingTables = collect([
            'access_gate_areas',
            'access_gate_registrations',
            'access_gate_grants',
            'access_gate_claim_tokens',
            'access_gate_browser_tokens',
            'access_gate_events',
        ])->reject(fn (string $table): bool => Schema::connection($connectionName)->hasTable($table));

        if ($missingTables->isNotEmpty()) {
            $this->error(__('capell-access-gate::doctor.database.missing_tables', [
                'tables' => $missingTables->implode(', '),
            ]));

            return 1;
        }

        $this->info(__('capell-access-gate::doctor.database.ok', ['connection' => $connectionName]));

        return 0;
    }

    private function checkMiddleware(Router $router): int
    {
        if (! array_key_exists('access-gate', $router->getMiddleware())) {
            $this->error(__('capell-access-gate::doctor.middleware.alias_missing'));

            return 1;
        }

        $webMiddleware = $router->getMiddlewareGroups()['web'] ?? [];
        $accessGatePosition = $this->firstMiddlewarePosition($webMiddleware, ['access-gate']);
        $pageCachePosition = $this->firstMiddlewarePosition($webMiddleware, $this->pageCacheAliases());

        if ($pageCachePosition !== null && $accessGatePosition !== null && $accessGatePosition > $pageCachePosition) {
            $this->error(__('capell-access-gate::doctor.middleware.page_cache_before_gate'));

            return 1;
        }

        if ($pageCachePosition !== null && $accessGatePosition === null) {
            $this->error(__('capell-access-gate::doctor.middleware.route_level_required'));

            return 1;
        }

        $this->info(__('capell-access-gate::doctor.middleware.ok'));

        return 0;
    }

    private function checkCookies(): int
    {
        $sameSite = strtolower((string) config('access-gate.cookies.browser_token.same_site', 'lax'));
        $secure = config('access-gate.cookies.browser_token.secure');

        if (! in_array($sameSite, ['lax', 'strict', 'none'], true)) {
            $this->error(__('capell-access-gate::doctor.cookies.invalid_same_site'));

            return 1;
        }

        if ($sameSite === 'none' && $secure !== true) {
            $this->error(__('capell-access-gate::doctor.cookies.none_requires_secure'));

            return 1;
        }

        if (app()->environment('production') && $secure !== true) {
            $this->warn(__('capell-access-gate::doctor.cookies.production_secure'));
        }

        $this->info(__('capell-access-gate::doctor.cookies.ok'));

        return 0;
    }

    private function checkClaimHosts(): void
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($appHost) || $appHost === '') {
            $this->warn(__('capell-access-gate::doctor.claim_hosts.app_url_missing'));

            return;
        }

        $areasWithMissingHost = Area::query()
            ->get()
            ->filter(function (Area $area) use ($appHost): bool {
                $claimHosts = $area->claim_url_hosts ?? [];

                return $claimHosts !== [] && ! in_array($appHost, $claimHosts, true);
            });

        if ($areasWithMissingHost->isNotEmpty()) {
            $this->warn(__('capell-access-gate::doctor.claim_hosts.app_host_not_listed', [
                'areas' => $areasWithMissingHost->pluck('key')->implode(', '),
            ]));

            return;
        }

        $this->info(__('capell-access-gate::doctor.claim_hosts.ok'));
    }

    /**
     * @param  list<string>  $middleware
     * @param  list<string>  $aliases
     */
    private function firstMiddlewarePosition(array $middleware, array $aliases): ?int
    {
        foreach ($middleware as $position => $middlewareName) {
            if (! is_string($middlewareName)) {
                continue;
            }

            $middlewareAlias = str($middlewareName)->before(':')->toString();

            if (in_array($middlewareAlias, $aliases, true)) {
                return (int) $position;
            }
        }

        return null;
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
}
